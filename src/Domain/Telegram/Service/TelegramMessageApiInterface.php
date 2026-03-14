<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\ValueObject\Bot\TelegramEditMessage;
use App\Domain\Telegram\ValueObject\Bot\TelegramEditReplyMarkup;
use App\Domain\Telegram\ValueObject\Bot\TelegramSendMessage;
use App\Domain\Telegram\ValueObject\TelegramMessage;

interface TelegramMessageApiInterface
{
    public function sendMessage(TelegramSendMessage $message): ?TelegramMessage;

    public function deleteMessage(int $chatId, int $messageId): bool;

    public function editMessageText(TelegramEditMessage $message): bool;

    public function editMessageReplyMarkup(TelegramEditReplyMarkup $markup): bool;
}
