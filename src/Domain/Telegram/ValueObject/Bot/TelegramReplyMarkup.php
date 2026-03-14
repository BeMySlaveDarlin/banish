<?php

declare(strict_types=1);

namespace App\Domain\Telegram\ValueObject\Bot;

final class TelegramReplyMarkup
{
    /** @var TelegramInlineKeyboard | array<int, array<string, string>> */
    public TelegramInlineKeyboard | array $inline_keyboard = [];
}
