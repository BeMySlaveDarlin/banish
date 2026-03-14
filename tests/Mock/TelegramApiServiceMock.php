<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Domain\Telegram\Service\TelegramApiServiceInterface;
use App\Domain\Telegram\ValueObject\Bot\TelegramChatMember;
use App\Domain\Telegram\ValueObject\Bot\TelegramEditMessage;
use App\Domain\Telegram\ValueObject\Bot\TelegramEditReplyMarkup;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Domain\Telegram\ValueObject\Bot\TelegramWebHookInfo;
use App\Domain\Telegram\ValueObject\TelegramMessage;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;

final class TelegramApiServiceMock implements TelegramApiServiceInterface
{
    /** @var array<int, array{method: string, args: array<int, mixed>}> */
    private array $callLog = [];

    public bool $banResult = true;
    public bool $unbanResult = true;
    public bool $deleteMessageResult = true;
    public bool $editMessageTextResult = true;
    public bool $editMessageReplyMarkupResult = true;
    public bool $deleteWebhookResult = true;
    public bool $setWebhookResult = true;
    public ?TelegramMessage $sendMessageResult = null;
    public ?TelegramChatMember $chatMemberResult;

    public function __construct()
    {
        $this->chatMemberResult = self::createDefaultChatMember();
    }
    public ?TelegramWebHookInfo $webhookInfoResult = null;
    /** @var array<string, mixed> */
    public array $updatesResult = [];
    private bool $sendMessageDefault = true;

    public function willReturn(string $method, mixed $result): self
    {
        match ($method) {
            'banChatMember' => $this->banResult = (bool) $result,
            'unbanChatMember' => $this->unbanResult = (bool) $result,
            'deleteMessage' => $this->deleteMessageResult = (bool) $result,
            'editMessageText' => $this->editMessageTextResult = (bool) $result,
            'editMessageReplyMarkup' => $this->editMessageReplyMarkupResult = (bool) $result,
            'deleteWebhook' => $this->deleteWebhookResult = (bool) $result,
            'setWebhook' => $this->setWebhookResult = (bool) $result,
            'sendMessage' => $this->setSendMessageResult($result),
            'getChatMember', 'getChatMemberFromApi' => $this->chatMemberResult = $result instanceof TelegramChatMember ? $result : null,
            'getWebhookInfo' => $this->webhookInfoResult = $result instanceof TelegramWebHookInfo ? $result : null,
            'getUpdates' => $this->updatesResult = is_array($result) ? $result : [],
            default => null,
        };

        return $this;
    }

    /**
     * @return array<int, array{method: string, args: array<int, mixed>}>
     */
    public function getCallLog(): array
    {
        return $this->callLog;
    }

    /**
     * @return array{method: string, args: array<int, mixed>}|null
     */
    public function getLastCall(string $method): ?array
    {
        $filtered = array_filter($this->callLog, static fn (array $entry): bool => $entry['method'] === $method);

        if ($filtered === []) {
            return null;
        }

        return end($filtered);
    }

    public function getCallCount(string $method): int
    {
        return count(array_filter($this->callLog, static fn (array $entry): bool => $entry['method'] === $method));
    }

    public function getChatMember(?int $chatId, ?int $userId): ?TelegramChatMember
    {
        $this->record('getChatMember', [$chatId, $userId]);

        return $this->chatMemberResult;
    }

    public function getChatMemberFromApi(?int $chatId, ?int $userId): ?TelegramChatMember
    {
        $this->record('getChatMemberFromApi', [$chatId, $userId]);

        return $this->chatMemberResult;
    }

    public function banChatMember(int $chatId, int $userId): bool
    {
        $this->record('banChatMember', [$chatId, $userId]);

        return $this->banResult;
    }

    public function unbanChatMember(int $chatId, int $userId): bool
    {
        $this->record('unbanChatMember', [$chatId, $userId]);

        return $this->unbanResult;
    }

    public function sendMessage(TelegramSendMessage $message): ?TelegramMessage
    {
        $this->record('sendMessage', [$message]);

        if ($this->sendMessageResult !== null) {
            return $this->sendMessageResult;
        }

        if ($this->sendMessageDefault) {
            return self::createDefaultMessage();
        }

        return null;
    }

    public function deleteMessage(int $chatId, int $messageId): bool
    {
        $this->record('deleteMessage', [$chatId, $messageId]);

        return $this->deleteMessageResult;
    }

    public function editMessageText(TelegramEditMessage $message): bool
    {
        $this->record('editMessageText', [$message]);

        return $this->editMessageTextResult;
    }

    public function editMessageReplyMarkup(TelegramEditReplyMarkup $markup): bool
    {
        $this->record('editMessageReplyMarkup', [$markup]);

        return $this->editMessageReplyMarkupResult;
    }

    public function getWebhookInfo(): ?TelegramWebHookInfo
    {
        $this->record('getWebhookInfo', []);

        return $this->webhookInfoResult;
    }

    public function deleteWebhook(): bool
    {
        $this->record('deleteWebhook', []);

        return $this->deleteWebhookResult;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getUpdates(array $params = []): array
    {
        $this->record('getUpdates', [$params]);

        return $this->updatesResult;
    }

    public function setWebhook(string $url): bool
    {
        $this->record('setWebhook', [$url]);

        return $this->setWebhookResult;
    }

    public function reset(): void
    {
        $this->callLog = [];
        $this->banResult = true;
        $this->unbanResult = true;
        $this->deleteMessageResult = true;
        $this->editMessageTextResult = true;
        $this->editMessageReplyMarkupResult = true;
        $this->deleteWebhookResult = true;
        $this->setWebhookResult = true;
        $this->sendMessageResult = null;
        $this->chatMemberResult = self::createDefaultChatMember();
        $this->webhookInfoResult = null;
        $this->updatesResult = [];
        $this->sendMessageDefault = true;
    }

    private static function createDefaultChatMember(): TelegramChatMember
    {
        $member = new TelegramChatMember();
        $member->status = 'member';
        $member->user = new TelegramMessageFrom();
        $member->user->id = 0;
        $member->user->is_bot = false;
        $member->user->first_name = 'User';

        return $member;
    }

    private static function createDefaultMessage(): TelegramMessage
    {
        $message = new TelegramMessage();
        $message->message_id = 1;
        $message->date = time();
        $message->chat = new TelegramMessageChat();
        $message->from = new TelegramMessageFrom();

        return $message;
    }

    /**
     * @param array<int, mixed> $args
     */
    private function record(string $method, array $args): void
    {
        $this->callLog[] = ['method' => $method, 'args' => $args];
    }

    private function setSendMessageResult(mixed $result): null
    {
        if ($result instanceof TelegramMessage) {
            $this->sendMessageResult = $result;
        } elseif ($result === null) {
            $this->sendMessageResult = null;
            $this->sendMessageDefault = false;
        }

        return null;
    }
}
