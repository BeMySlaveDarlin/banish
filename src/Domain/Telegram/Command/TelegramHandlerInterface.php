<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Command;

interface TelegramHandlerInterface
{
    public function handle(TelegramCommandInterface $command): string;
}
