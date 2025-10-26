<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Domain\Telegram\ValueObject\TelegramUpdate;

interface RouteRegistryInterface
{
    public function matches(TelegramUpdate $update, string $botName): bool;

    public function getCommand(TelegramUpdate $update, string $botName): string;
}
