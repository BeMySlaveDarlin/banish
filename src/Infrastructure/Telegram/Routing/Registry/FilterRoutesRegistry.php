<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class FilterRoutesRegistry implements RouteRegistryInterface
{
    public function matches(TelegramUpdate $update, string $botName): bool
    {
        if ($update->message_reaction !== null) {
            return false;
        }

        return $update->getFrom()->is_bot || $update->getMessageObj()->isEmpty();
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        return UnsupportedCommand::class;
    }
}
