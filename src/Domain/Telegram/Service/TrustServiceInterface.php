<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;

interface TrustServiceInterface
{
    public function isUserTrusted(TelegramChatEntity $chat, int $userId): bool;
}
