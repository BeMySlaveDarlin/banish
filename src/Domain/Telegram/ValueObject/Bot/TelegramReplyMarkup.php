<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject\Bot;

class TelegramReplyMarkup
{
    public TelegramInlineKeyboard | array $inline_keyboard = [];
}
