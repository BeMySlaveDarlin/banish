<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Command;

interface TelegramCommandInterface
{
    public function getChatId(): int;

    public function getNewStatus(): ?string;

    public function getOldStatus(): ?string;

    public function getUserId(): int;
}
