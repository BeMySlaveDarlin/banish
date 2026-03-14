<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\ValueObject\TelegramMessage;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

interface SpammerMessageServiceInterface
{
    public function getSpammerMessage(TelegramUpdate $update): ?TelegramMessage;
}
