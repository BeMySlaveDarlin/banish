<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject;

class TelegramMessage
{
    public ?int $message_id = null;
    public int $date = 0;

    public ?string $text = null;
    /**
     * @var TelegramMessageEntity[]
     */
    public ?array $entities = null;

    public TelegramMessageChat $chat;
    public TelegramMessageFrom $from;
    public ?TelegramMessageFrom $left_chat_member = null;
    public ?TelegramMessageFrom $left_chat_participant = null;
    public ?TelegramMessageFrom $new_chat_member = null;
    public ?TelegramMessageFrom $new_chat_participant = null;
    public ?TelegramMessage $reply_to_message = null;
    public ?TelegramMessageLink $link_preview_options = null;
    public ?TelegramMessageSticker $sticker = null;
    public ?TelegramMessageDocument $document = null;
    public ?TelegramMessageCommand $messageCommand = null;

    public function isEmpty(): bool
    {
        return $this->message_id === null;
    }

    public function getLeftChatMember(): ?TelegramMessageFrom
    {
        return $this->left_chat_member ?? $this->left_chat_participant ?? null;
    }

    public function getJoinChatMember(): ?TelegramMessageFrom
    {
        return $this->new_chat_member ?? $this->new_chat_participant ?? null;
    }

    public function isLink(): bool
    {
        return $this->link_preview_options !== null;
    }

    public function isSticker(): bool
    {
        return $this->sticker !== null;
    }

    public function isDocument(): bool
    {
        return $this->document !== null;
    }

    public function isReply(): bool
    {
        return null !== $this->reply_to_message;
    }

    public function isBotMention(string $botName): bool
    {
        if (empty($this->entities)) {
            return false;
        }

        foreach ($this->entities as $entity) {
            if ($entity->type === 'mention' && stripos($this->text, $botName) !== false) {
                return true;
            }
        }

        return false;
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
            $filtered = str_ireplace("@$botName", '', $this->text);
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
