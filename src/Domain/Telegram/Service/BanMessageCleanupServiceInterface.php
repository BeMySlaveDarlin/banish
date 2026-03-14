<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

interface BanMessageCleanupServiceInterface
{
    public function clearBotMessages(): void;
}
