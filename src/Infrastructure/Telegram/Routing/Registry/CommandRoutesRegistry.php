<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use App\Infrastructure\Telegram\Dispatcher\CommandHandlerFactory;

final readonly class CommandRoutesRegistry implements RouteRegistryInterface
{
    public function __construct(
        private CommandHandlerFactory $commandHandlerFactory
    ) {
    }

    public function matches(TelegramUpdate $update, string $botName): bool
    {
        if (!$update->getMessageObj()->isBotCommand()) {
            return false;
        }

        $command = $update->getMessageObj()->getCommand($botName);

        return $command !== null && isset($this->commandHandlerFactory->getCommandRegistryMap()[$command->command]);
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        $command = $update->getMessageObj()->getCommand($botName);

        return $this->commandHandlerFactory->getCommandRegistryMap()[$command?->command] ?? UnsupportedCommand::class;
    }

    public static function getPriority(): int
    {
        return 90;
    }
}
