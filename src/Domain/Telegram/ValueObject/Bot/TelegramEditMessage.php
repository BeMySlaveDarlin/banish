<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject\Bot;

class TelegramEditMessage
{
    /**
     * @param TelegramReplyMarkup|array<int, array<string, string>> $reply_markup
     */
    public function __construct(
        public int $chat_id,
        public int $message_id,
        public string $text,
        public TelegramReplyMarkup | array $reply_markup = []
    ) {
        if (empty($this->reply_markup)) {
            $this->reply_markup = new TelegramReplyMarkup();
        }
    }
}
