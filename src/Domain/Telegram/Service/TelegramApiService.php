<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\ValueObject\Bot\TelegramChatMember;
use App\Domain\Telegram\ValueObject\Bot\TelegramEditMessage;
use App\Domain\Telegram\ValueObject\Bot\TelegramEditReplyMarkup;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Domain\Telegram\ValueObject\Bot\TelegramWebHookInfo;
use App\Domain\Telegram\ValueObject\TelegramMessage;
use Psr\Cache\CacheItemPoolInterface;
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
    private const string ACTION_UNBAN_CHAT_MEMBER = '/unbanChatMember';
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
        self::ACTION_UNBAN_CHAT_MEMBER => 5,
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
    private const int CHAT_MEMBER_CACHE_TTL = 300;

    /** @var array<string, HttpClientInterface> */
    private array $httpClients = [];

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger,
        private readonly CacheItemPoolInterface $cache,
        private readonly string $botToken,
        private readonly string $apiUrl
    ) {
    }

    public function getChatMember(?int $chatId, ?int $userId): ?TelegramChatMember
    {
        if (null === $chatId || null === $userId) {
            return null;
        }

        return $this->getChatMemberFromApi($chatId, $userId);
    }

    public function getChatMemberFromApi(?int $chatId, ?int $userId): ?TelegramChatMember
    {
        if (null === $chatId || null === $userId) {
            return null;
        }

        $cacheKey = "chat_member_{$chatId}_{$userId}";
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $result = $this->send(self::ACTION_GET_CHAT_MEMBER, ['chat_id' => $chatId, 'user_id' => $userId]);
        if (empty($result)) {
            return null;
        }

        try {
            $member = $this->serializer->deserialize($result, TelegramChatMember::class, 'json');
            $cacheItem->set($member)->expiresAfter(self::CHAT_MEMBER_CACHE_TTL);
            $this->cache->save($cacheItem);

            return $member;
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

    public function unbanChatMember(int $chatId, int $userId): bool
    {
        $result = $this->send(self::ACTION_UNBAN_CHAT_MEMBER, [
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
        try {
            $uri = $this->apiUrl . $this->botToken . self::ACTION_DELETE_MESSAGE;
            $options = $this->getOptions(['chat_id' => $chatId, 'message_id' => $messageId]);
            $httpClient = $this->getHttpClient(self::ACTION_DELETE_MESSAGE);
            $response = $httpClient->request('POST', $uri, $options);

            if (!in_array($response->getStatusCode(), [200, 201, 204], true)) {
                return false;
            }

            $content = $response->getContent();
            if (empty($content)) {
                return false;
            }

            if (!json_validate($content)) {
                return false;
            }

            /** @var array<string, string> $json */
            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!isset($json['ok']) || !$json['ok']) {
                $description = $json['description'] ?? 'Unknown error';
                if (is_string($description) && str_contains($description, 'message to delete not found')) {
                    $this->logger->debug('Message already deleted', [
                        'chatId' => $chatId,
                        'messageId' => $messageId,
                    ]);

                    return true;
                }

                $this->logger->warning('Failed to delete message', [
                    'chatId' => $chatId,
                    'messageId' => $messageId,
                    'error' => $description,
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            $this->logger->warning('Error deleting message', [
                'chatId' => $chatId,
                'messageId' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function getUpdates(array $params = []): array
    {
        $result = $this->send(self::ACTION_GET_UPDATES, $params);

        if (empty($result)) {
            return [];
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
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

            /** @var array<string, mixed> $json */
            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (!isset($json['ok']) || !$json['ok']) {
                $description = $json['description'] ?? 'Error response state';
                $errorMsg = is_string($description) ? $description : 'Error response state';
                throw new BadRequestException($errorMsg);
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

    /**
     * @return array<string, string>
     */
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
