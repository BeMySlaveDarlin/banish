<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\ValueObject\Bot\TelegramChatMember;
use App\Domain\Telegram\ValueObject\Bot\TelegramEditMessage;
use App\Domain\Telegram\ValueObject\Bot\TelegramEditReplyMarkup;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Domain\Telegram\ValueObject\Bot\TelegramWebHookInfo;
use App\Domain\Telegram\ValueObject\TelegramMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class TelegramApiService
{
    public const int JSON_OPTIONS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    private const string ACTION_GET_CHAT_MEMBER = '/getChatMember';
    private const string ACTION_BAN_CHAT_MEMBER = '/banChatMember';
    private const string ACTION_SEND_MESSAGE = '/sendMessage';
    private const string ACTION_DELETE_MESSAGE = '/deleteMessage';
    private const string ACTION_EDIT_MESSAGE_TEXT = '/editMessageText';
    private const string ACTION_EDIT_MESSAGE_KB = '/editMessageReplyMarkup';
    private const string ACTION_GET_WEBHOOK_INFO = '/getWebhookInfo';
    private const string ACTION_DELETE_WEBHOOK = '/deleteWebhook';
    private const string ACTION_GET_UPDATES = '/getUpdates';
    private const string ACTION_SET_WEBHOOK = '/setWebhook';

    private const array TIMEOUTS = [
        self::ACTION_GET_CHAT_MEMBER => 5,
        self::ACTION_BAN_CHAT_MEMBER => 5,
        self::ACTION_SEND_MESSAGE => 10,
        self::ACTION_DELETE_MESSAGE => 5,
        self::ACTION_EDIT_MESSAGE_TEXT => 10,
        self::ACTION_EDIT_MESSAGE_KB => 10,
        self::ACTION_GET_WEBHOOK_INFO => 5,
        self::ACTION_DELETE_WEBHOOK => 5,
        self::ACTION_GET_UPDATES => 60,
        self::ACTION_SET_WEBHOOK => 10,
    ];

    private const int DEFAULT_TIMEOUT = 10;

    private array $httpClients = [];

    public function __construct(
        private SerializerInterface $serializer,
        private LoggerInterface $logger,
        private string $botToken,
        private string $apiUrl
    ) {
    }

    public function getChatMember(int $chatId, int $userId): ?TelegramChatMember
    {
        $result = $this->send(self::ACTION_GET_CHAT_MEMBER, ['chat_id' => $chatId, 'user_id' => $userId]);

        if (empty($result)) {
            return null;
        }

        try {
            return $this->serializer->deserialize($result, TelegramChatMember::class, 'json');
        } catch (Throwable $e) {
            $this->logger->error('Failed to deserialize TelegramChatMember', [
                'error' => $e->getMessage(),
                'result' => $result,
            ]);

            return null;
        }
    }

    public function banChatMember(int $chatId, int $userId): bool
    {
        $result = $this->send(self::ACTION_BAN_CHAT_MEMBER, [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);

        return !empty($result);
    }

    public function sendMessage(TelegramSendMessage $message): ?TelegramMessage
    {
        $result = $this->send(self::ACTION_SEND_MESSAGE, $message);

        if (empty($result)) {
            return null;
        }

        try {
            return $this->serializer->deserialize($result, TelegramMessage::class, 'json');
        } catch (Throwable $e) {
            $this->logger->error('Failed to deserialize TelegramMessage', [
                'error' => $e->getMessage(),
                'result' => $result,
            ]);

            return null;
        }
    }

    public function deleteMessage(int $chatId, int $messageId): bool
    {
        $result = $this->send(self::ACTION_DELETE_MESSAGE, ['chat_id' => $chatId, 'message_id' => $messageId]);

        return !empty($result);
    }

    public function editMessageText(TelegramEditMessage $message): bool
    {
        $result = $this->send(self::ACTION_EDIT_MESSAGE_TEXT, $message);

        return !empty($result);
    }

    public function editMessageReplyMarkup(TelegramEditReplyMarkup $markup): bool
    {
        $result = $this->send(self::ACTION_EDIT_MESSAGE_KB, $markup);

        return !empty($result);
    }

    public function getWebhookInfo(): ?TelegramWebHookInfo
    {
        $result = $this->send(self::ACTION_GET_WEBHOOK_INFO, []);

        if (empty($result)) {
            return null;
        }

        try {
            return $this->serializer->deserialize($result, TelegramWebHookInfo::class, 'json');
        } catch (Throwable $e) {
            $this->logger->error('Failed to deserialize TelegramWebHookInfo', [
                'error' => $e->getMessage(),
                'result' => $result,
            ]);

            return null;
        }
    }

    public function deleteWebhook(): bool
    {
        $result = $this->send(self::ACTION_DELETE_WEBHOOK, []);

        return !empty($result);
    }

    public function getUpdates(array $params = []): array
    {
        $result = $this->send(self::ACTION_GET_UPDATES, $params);

        if (empty($result)) {
            return [];
        }

        return json_decode($result);
    }

    public function setWebhook(string $url): bool
    {
        $result = $this->send(self::ACTION_SET_WEBHOOK, ['url' => $url]);

        return !empty($result);
    }

    private function getHttpClient(string $action): HttpClientInterface
    {
        if (!isset($this->httpClients[$action])) {
            $timeout = self::TIMEOUTS[$action] ?? self::DEFAULT_TIMEOUT;

            $this->httpClients[$action] = HttpClient::create([
                'timeout' => $timeout,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        }

        return $this->httpClients[$action];
    }

    private function send(string $action, mixed $params): ?string
    {
        try {
            $uri = $this->apiUrl . $this->botToken . $action;
            $options = $this->getOptions($params);
            $httpClient = $this->getHttpClient($action);
            $response = $httpClient->request('POST', $uri, $options);
            if (!in_array($response->getStatusCode(), [200, 201, 204], true)) {
                $this->logger->warning('Telegram API request failed', [
                    'action' => $action,
                    'params' => $params,
                    'error' => $response->getContent(false),
                ]);

                return null;
            }

            $content = $response->getContent();
            if (empty($content)) {
                throw new BadRequestException('Empty response');
            }

            if (!json_validate($content)) {
                throw new BadRequestException('Invalid response JSON');
            }

            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!isset($json['ok']) || !$json['ok']) {
                throw new BadRequestException($json['description'] ?? 'Error response state');
            }

            $this->logger->info('Telegram API request sent', [
                'action' => $action,
                'params' => $params,
                'response' => $json,
            ]);

            return json_encode($json['result'] ?? '', self::JSON_OPTIONS);
        } catch (Throwable $throwable) {
            $this->logger->warning('Telegram API request failed', [
                'action' => $action,
                'params' => $params,
                'error' => $throwable->getMessage(),
            ]);

            return null;
        }
    }

    private function getOptions(mixed $params = null): array
    {
        if (is_array($params) || is_object($params)) {
            return [
                'body' => json_encode($params, self::JSON_OPTIONS),
            ];
        }

        if (is_string($params)) {
            return [
                'body' => $params,
            ];
        }

        return [];
    }
}
