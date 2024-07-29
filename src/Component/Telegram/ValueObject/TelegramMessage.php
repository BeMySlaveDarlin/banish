<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject;

class TelegramMessage
{
    public int | string $message_id;
    public int $date;
    public TelegramMessageChat $chat;
    public TelegramMessageFrom $from;
    public ?TelegramMessage $reply_to_message = null;
    public ?TelegramMessageLink $link_preview_options = null;
    public ?TelegramMessageCommand $messageCommand = null;

    public ?string $text = null;
    /**
     * @var TelegramMessageEntity[]
     */
    public ?array $entities = null;

    public function isLink(): bool
    {
        return $this->link_preview_options !== null;
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

            $this->messageCommand = new TelegramMessageCommand($command, $parts);
        }

        return $this->messageCommand;
    }
}
