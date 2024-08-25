<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject\Bot;

class TelegramEditMessage
{
    public function __construct(
        public int $chat_id,
        public int $message_id,
        public string $text
    ) {
    }
}
