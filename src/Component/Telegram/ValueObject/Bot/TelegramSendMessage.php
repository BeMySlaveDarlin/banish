<?php

declare(strict_types=1);

namespace App\Component\Telegram\ValueObject\Bot;

class TelegramSendMessage
{
    public function __construct(
        public string $chat_id,
        public string $text,
        public TelegramReplyMarkup | array $reply_markup = [],
        public string $parse_mode = 'Markdown'
    ) {
        if (empty($this->reply_markup)) {
            $this->reply_markup = new TelegramReplyMarkup();
        }
    }
}
