<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Routing\Registry;

use App\Application\Command\Telegram\Admin\SetMinMessagesForTrustCommand;
use App\Application\Command\Telegram\Admin\SetVotesLimitCommand;
use App\Application\Command\Telegram\Admin\ToggleBotCommand;
use App\Application\Command\Telegram\Admin\ToggleDeleteMessageCommand;
use App\Application\Command\Telegram\HelpCommand;
use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class CommandRoutesRegistry implements RouteRegistryInterface
{
    private const array COMMAND_MAP = [
        '/start' => HelpCommand::class,
        '/help' => HelpCommand::class,
        '/toggleBot' => ToggleBotCommand::class,
        '/votesLimit' => SetVotesLimitCommand::class,
        '/toggleDeleteMessage' => ToggleDeleteMessageCommand::class,
        '/setMinMessagesForTrust' => SetMinMessagesForTrustCommand::class,
    ];

    public function getPriority(): int
    {
        return 30;
    }

    public function matches(TelegramUpdate $update, string $botName): bool
    {
        if (!$update->getMessageObj()->isBotCommand()) {
            return false;
        }

        $command = $update->getMessageObj()->getCommand($botName);

        return $command !== null && isset(self::COMMAND_MAP[$command->command]);
    }

    public function getCommand(TelegramUpdate $update, string $botName): string
    {
        $command = $update->getMessageObj()->getCommand($botName);

        return self::COMMAND_MAP[$command?->command] ?? UnsupportedCommand::class;
    }
}
