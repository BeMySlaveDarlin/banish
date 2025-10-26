<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Middleware;

use App\Domain\Telegram\Command\TelegramCommandInterface;

interface MiddlewareInterface
{
    public function handle(TelegramCommandInterface $command): TelegramCommandInterface;
}
