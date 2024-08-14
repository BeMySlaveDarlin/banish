<?php

declare(strict_types=1);

namespace App\Component\Telegram\Policy;

use App\Component\Telegram\ValueObject\Bot\TelegramChatMember;
use App\Component\Telegram\ValueObject\Bot\TelegramWebHookInfo;
use App\Component\Telegram\ValueObject\TelegramMessage;
use App\Component\Telegram\ValueObject\TelegramUpdate;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

readonly class TelegramApiClientPolicy
{
    public const string ACTION_GET_WEBHOOK_INFO = '/getWebhookInfo';
    public const string ACTION_DELETE_WEBHOOK = '/deleteWebhook';
    public const string ACTION_GET_UPDATES = '/getUpdates';
    public const string ACTION_SET_WEBHOOK = '/setWebhook';
    public const string ACTION_GET_CHAT_MEMBER = '/getChatMember';
    public const string ACTION_BAN_CHAT_MEMBER = '/banChatMember';
    public const string ACTION_SEND_MESSAGE = '/sendMessage';
    public const string ACTION_DELETE_MESSAGE = '/deleteMessage';
    public const string ACTION_EDIT_MESSAGE_TEXT = '/editMessageText';
    public const string ACTION_EDIT_MESSAGE_KB = '/editMessageReplyMarkup';

    public HttpClientInterface $httpClient;

    public function __construct(
        private SerializerInterface $serializer,
        private LoggerInterface $logger,
        private TelegramConfigPolicy $configPolicy
    ) {
        $this->httpClient = HttpClient::create([
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function getWebhookInfo(): ?TelegramWebHookInfo
    {
        $result = $this->send(self::ACTION_GET_WEBHOOK_INFO, []);
        if (empty($result)) {
            return null;
        }

        return $this->serializer->deserialize($result, TelegramWebHookInfo::class, 'json');
    }

    public function deleteWebhook(): bool
    {
        $result = $this->send(self::ACTION_DELETE_WEBHOOK, []);
        if (empty($result)) {
            return false;
        }

        return $result === 'true';
    }

    public function setWebhook(string $url): bool
    {
        $result = $this->send(self::ACTION_SET_WEBHOOK, ['url' => $url]);
        if (empty($result)) {
            return false;
        }

        return $result === 'true';
    }

    /**
     * @return null|TelegramUpdate[]
     */
    public function getUpdates(array $params = []): ?array
    {
        $result = $this->send(self::ACTION_GET_UPDATES, $params);
        if (empty($result)) {
            return null;
        }

        return $this->serializer->deserialize($result, 'App\Component\Telegram\ValueObject\TelegramUpdate[]', 'json');
    }

    public function getChatMember(int $chatId, int $userId): ?TelegramChatMember
    {
        $result = $this->send(self::ACTION_GET_CHAT_MEMBER, [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);
        if (empty($result)) {
            return null;
        }

        return $this->serializer->deserialize($result, TelegramChatMember::class, 'json');
    }

    public function banChatMember(int $chatId, int $userId): ?TelegramChatMember
    {
        $result = $this->send(self::ACTION_BAN_CHAT_MEMBER, [
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);
        if (empty($result)) {
            return null;
        }

        return $this->serializer->deserialize($result, TelegramChatMember::class, 'json');
    }

    public function deleteMessage(int $chatId, int $messageId): ?string
    {
        return $this->send(self::ACTION_DELETE_MESSAGE, [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    public function editMessageText(mixed $message = null): ?string
    {
        return $this->send(self::ACTION_EDIT_MESSAGE_TEXT, $message);
    }

    public function editMessageKb(mixed $message = null): ?string
    {
        return $this->send(self::ACTION_EDIT_MESSAGE_KB, $message);
    }

    public function sendMessage(mixed $message = null): ?TelegramMessage
    {
        $result = $this->send(self::ACTION_SEND_MESSAGE, $message);
        if (empty($result)) {
            return null;
        }

        return $this->serializer->deserialize($result, TelegramMessage::class, 'json');
    }

    private function send(string $action, mixed $params = null): ?string
    {
        try {
            $uri = $this->configPolicy->getApiUrl() . $action;
            $options = $this->getOptions($params);
            $response = $this->httpClient->request('POST', $uri, $options);
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
                throw new BadRequestException('Error response state');
            }

            $this->logger->info('Telegram API request sent', [
                'action' => $action,
                'params' => $params,
                'response' => $json,
            ]);

            return json_encode($json['result'] ?? '', TelegramConfigPolicy::JSON_OPTIONS);
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
                'body' => json_encode($params, TelegramConfigPolicy::JSON_OPTIONS),
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
