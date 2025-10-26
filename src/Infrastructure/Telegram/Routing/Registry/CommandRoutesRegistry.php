<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use App\Infrastructure\Telegram\Dispatcher\CommandHandlerFactory;

final readonly class CommandRoutesRegistry implements RouteRegistryInterface
{
    public function matches(TelegramUpdate $update, string $botName): bool
    {
        if (!$update->getMessageObj()->isBotCommand()) {
            return false;
        }

        $command = $update->getMessageObj()->getCommand($botName);

        return $command !== null && isset(CommandHandlerFactory::COMMAND_REGISTRY_MAP[$command->command]);
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        $command = $update->getMessageObj()->getCommand($botName);

        return CommandHandlerFactory::COMMAND_REGISTRY_MAP[$command?->command] ?? UnsupportedCommand::class;
    }
}
