<?php

declare(strict_types=1);

namespace App\Application\Command\Telegram;

use App\Domain\Telegram\Command\TelegramCommandInterface;

class UnsupportedCommand implements TelegramCommandInterface
{
    public function getChatId(): int
    {
        return 0;
    }

    public function getUserId(): int
    {
        return 0;
    }

    public function getNewStatus(): ?string
    {
        return null;
    }

    public function getOldStatus(): ?string
    {
        return null;
    }
}
