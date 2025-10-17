<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\HelpCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class PrivateChatRoutesRegistry implements RouteRegistryInterface
{
    public function getPriority(): int
    {
        return 40;
    }

    public function matches(TelegramUpdate $update, string $botName): bool
    {
        return $update->getChat()->isPrivate();
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        return HelpCommand::class;
    }
}
