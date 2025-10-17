<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Dispatcher;

use App\Application\Command\Telegram\Admin\SetMinMessagesForTrustCommand;
use App\Application\Command\Telegram\Admin\SetVotesLimitCommand;
use App\Application\Command\Telegram\Admin\ToggleBotCommand;
use App\Application\Command\Telegram\Admin\ToggleDeleteMessageCommand;
use App\Application\Command\Telegram\Ban\StartBanCommand;
use App\Application\Command\Telegram\Ban\VoteForBanCommand;
use App\Application\Command\Telegram\HelpCommand;
use App\Application\Command\Telegram\UnsupportedCommand;
use App\Application\Handler\Admin\SetMinMessagesForTrustHandler;
use App\Application\Handler\Admin\SetVotesLimitHandler;
use App\Application\Handler\Admin\ToggleBotHandler;
use App\Application\Handler\Admin\ToggleDeleteMessageHandler;
use App\Application\Handler\Ban\StartBanHandler;
use App\Application\Handler\Ban\VoteForBanHandler;
use App\Application\Handler\HelpHandler;
use App\Application\Handler\UnsupportedHandler;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use Psr\Container\ContainerInterface;

class HandlerFactory
{
    private const array COMMAND_HANDLER_MAP = [
        HelpCommand::class => HelpHandler::class,
        UnsupportedCommand::class => UnsupportedHandler::class,

        ToggleBotCommand::class => ToggleBotHandler::class,
        SetVotesLimitCommand::class => SetVotesLimitHandler::class,
        ToggleDeleteMessageCommand::class => ToggleDeleteMessageHandler::class,
        SetMinMessagesForTrustCommand::class => SetMinMessagesForTrustHandler::class,

        StartBanCommand::class => StartBanHandler::class,
        VoteForBanCommand::class => VoteForBanHandler::class,
    ];

    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function resolveCommandHandler(string $commandClass): TelegramHandlerInterface
    {
        $handlerClass = self::COMMAND_HANDLER_MAP[$commandClass] ?? null;
        if (!$handlerClass) {
            return $this->container->get(UnsupportedHandler::class);
        }

        return $this->container->get($handlerClass);
    }
}
