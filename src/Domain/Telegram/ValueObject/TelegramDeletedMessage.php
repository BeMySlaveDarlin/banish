<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject;

class TelegramDeletedMessage
{
    public TelegramMessageChat $chat;
    public int $message_id = 0;
    public int $date = 0;

    public function getChatId(): int
    {
        return (int) $this->chat->id;
    }

    public function getMessageId(): int
    {
        return $this->message_id;
    }
}
