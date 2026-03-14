<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\ValueObject\TelegramMessage;
use App\Domain\Telegram\ValueObject\TelegramMessageChat;
use App\Domain\Telegram\ValueObject\TelegramMessageFrom;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class SpammerMessageService implements SpammerMessageServiceInterface
{
    private const int PREVIOUS_MESSAGE_MAX_AGE_SECONDS = 30;

    public function __construct(
        private UserRepository $userRepository,
        private RequestHistoryRepository $requestHistoryRepository,
        private string $botName
    ) {
    }

    public function getSpammerMessage(TelegramUpdate $update): ?TelegramMessage
    {
        if ($this->isReply($update)) {
            return $this->getReplyMessage($update);
        }

        if ($this->isUserMention($update)) {
            return $this->getUserMentionedMessage($update);
        }

        return $this->getPreviousMessage($update);
    }

    private function isReply(TelegramUpdate $update): bool
    {
        return $update->getMessageObj()->hasReply();
    }

    private function getReplyMessage(TelegramUpdate $update): ?TelegramMessage
    {
        return $update->getMessageObj()->reply_to_message;
    }

    private function isUserMention(TelegramUpdate $update): bool
    {
        return $update->getMessageObj()->hasUserMention($this->botName);
    }

    private function getUserMentionedMessage(TelegramUpdate $update): ?TelegramMessage
    {
        $username = $update->getMessageObj()->getUserMention($this->botName);

        if ($username === null) {
            return null;
        }

        $chatId = $update->getChat()->id;
        if ($chatId === null) {
            return null;
        }

        $user = $this->userRepository->findByChatAndUsername(
            $chatId,
            $username
        );

        if (!$user) {
            return null;
        }

        $from = new TelegramMessageFrom();
        $from->id = $user->userId;
        $from->is_bot = $user->isBot;
        $from->first_name = $user->name ?? '';
        $from->username = $user->username;

        $chat = new TelegramMessageChat();
        $chat->id = $chatId;
        $chat->type = $update->getChat()->type;

        $message = new TelegramMessage();
        $message->from = $from;
        $message->chat = $chat;
        $message->date = time();
        $message->text = '';

        return $message;
    }

    private function getPreviousMessage(TelegramUpdate $update): ?TelegramMessage
    {
        $chatId = $update->getChat()->id;
        $fromId = $update->getFrom()->id;
        $messageId = $update->getMessageObj()->message_id;

        if ($chatId === null || $fromId === null || $messageId === null) {
            return null;
        }

        $history = $this->requestHistoryRepository->findPreviousMessage(
            $chatId,
            $fromId,
            $messageId
        );

        if (!$history) {
            return null;
        }

        $maxAge = new \DateTimeImmutable('-' . self::PREVIOUS_MESSAGE_MAX_AGE_SECONDS . ' seconds');
        if ($history->createdAt < $maxAge) {
            return null;
        }

        $requestData = $history->request?->toArray() ?? [];
        $messageData = $requestData['message'] ?? null;

        if (!$messageData || !is_array($messageData)) {
            return null;
        }

        $from = new TelegramMessageFrom();
        $from->id = isset($messageData['from']['id']) ? (int) $messageData['from']['id'] : null;
        $from->is_bot = (bool) ($messageData['from']['is_bot'] ?? false);
        $from->first_name = $messageData['from']['first_name'] ?? null;
        $from->last_name = $messageData['from']['last_name'] ?? null;
        $from->username = $messageData['from']['username'] ?? null;

        $chat = new TelegramMessageChat();
        $chat->id = isset($messageData['chat']['id']) ? (int) $messageData['chat']['id'] : null;
        $chat->type = $messageData['chat']['type'] ?? null;

        $message = new TelegramMessage();
        $message->message_id = isset($messageData['message_id']) ? (int) $messageData['message_id'] : null;
        $message->from = $from;
        $message->chat = $chat;
        $message->date = (int) ($messageData['date'] ?? 0);
        $message->text = $messageData['text'] ?? null;

        return $message;
    }
}
