<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class FallbackRoutesRegistry implements RouteRegistryInterface
{
    public function getPriority(): int
    {
        return 999;
    }

    public function matches(TelegramUpdate $update, string $botName): bool
    {
        return true;
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        return UnsupportedCommand::class;
    }
}
