<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\ValueObject\TelegramUpdate;

interface HistoryServiceInterface
{
    public function createRequestHistory(TelegramUpdate $update, mixed $result = null): void;
}
