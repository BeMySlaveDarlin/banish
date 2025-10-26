<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

class TelegramMessage
{
    public ?int $message_id = null;
    public int $date = 0;

    public ?string $text = null;

    public TelegramMessageChat $chat;
    public TelegramMessageFrom $from;
    public ?TelegramMessageSticker $sticker;
    public ?TelegramMessageDocument $document;
    public ?TelegramMessageFrom $left_chat_member = null;
    public ?TelegramMessageFrom $left_chat_participant = null;
    public ?TelegramMessageFrom $new_chat_member = null;
    public ?TelegramMessageFrom $new_chat_participant = null;
    public ?TelegramMessage $reply_to_message = null;
    public ?TelegramMessageLink $link_preview_options = null;
    public ?TelegramMessageCommand $messageCommand = null;

    /**
     * @var TelegramMessageEntity[]
     */
    public ?array $entities = null;

    public function isEmpty(): bool
    {
        return $this->message_id === null;
    }

    public function getLeftChatMemberFrom(): ?TelegramMessageFrom
    {
        return $this->left_chat_member ?? $this->left_chat_participant ?? null;
    }

    public function getJoinChatMemberFrom(): ?TelegramMessageFrom
    {
        return $this->new_chat_member ?? $this->new_chat_participant ?? null;
    }

    public function hasStickerData(): bool
    {
        return $this->sticker !== null;
    }

    public function hasDocumentData(): bool
    {
        return $this->document !== null;
    }

    public function hasLink(): bool
    {
        return $this->link_preview_options !== null;
    }

    public function hasReply(): bool
    {
        return null !== $this->reply_to_message;
    }

    public function hasBotMention(string $botName): bool
    {
        if ($botName === null || stripos($this->text ?? '', $botName) === false) {
            return false;
        }

        if (empty($this->entities)) {
            return false;
        }

        foreach ($this->entities as $entity) {
            if ($entity->type === 'mention') {
                return true;
            }
        }

        return false;
    }

    public function hasUserMention(string $botName): bool
    {
        return !empty($this->getUserMention($botName));
    }

    public function getUserMention(string $botName): ?string
    {
        if (empty($this->entities)) {
            return null;
        }

        foreach ($this->entities as $entity) {
            if ($entity->type === 'text_mention') {
                $rawUserId = $entity->user['id'] ?? '';
                $userId = is_int($rawUserId) ? (string) $rawUserId : $rawUserId;
                return is_string($userId) ? $userId : '';
            }
        }

        $text = str_ireplace($botName, '', $this->text ?? '');
        if (!str_contains($text, '@')) {
            return null;
        }
        $text = str_replace('@', '', $text);

        return trim($text, " \n\r\t\v\0\s");
    }

    public function isBotCommand(): bool
    {
        if (empty($this->entities)) {
            return false;
        }

        foreach ($this->entities as $entity) {
            if ($entity->type === 'bot_command') {
                return true;
            }
        }

        return false;
    }

    public function getCommand(string $botName = ''): ?TelegramMessageCommand
    {
        if (!$this->isBotCommand()) {
            return null;
        }

        if (null === $this->messageCommand) {
            $filtered = str_ireplace("@$botName", '', $this->text ?? '');
            $parts = explode(' ', $filtered);
            $command = array_shift($parts);
            foreach ($parts as &$part) {
                $part = trim($part);
            }
            unset($part);

            $this->messageCommand = new TelegramMessageCommand($command, $parts);
        }

        return $this->messageCommand;
    }
}
