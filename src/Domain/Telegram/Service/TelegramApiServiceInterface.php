<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

interface TelegramApiServiceInterface extends
    TelegramChatMemberApiInterface,
    TelegramMessageApiInterface,
    TelegramWebhookApiInterface
{
}
