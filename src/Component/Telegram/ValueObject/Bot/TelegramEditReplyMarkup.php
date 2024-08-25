<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject\Bot;

class TelegramEditReplyMarkup
{
    public function __construct(
        public int $chat_id,
        public int $message_id,
        public TelegramReplyMarkup | array $reply_markup = []
    ) {
        if (empty($this->reply_markup)) {
            $this->reply_markup = new TelegramReplyMarkup();
        }
    }
}
