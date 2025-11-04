<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Command;

use App\Domain\Telegram\ValueObject\TelegramUpdate;

interface TelegramCommandInterface
{
    public function getUpdate(): TelegramUpdate;

    public function getChatId(): int;

    public function getNewStatus(): ?string;

    public function getOldStatus(): ?string;

    public function getUserId(): int;
}
