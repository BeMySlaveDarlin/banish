<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Dispatcher;

use App\Application\Command\Telegram\Admin\RequestAdminLinkCommand;
use App\Application\Command\Telegram\Ban\StartBanCommand;
use App\Application\Command\Telegram\Ban\VoteForBanCommand;
use App\Application\Command\Telegram\HelpCommand;
use App\Application\Command\Telegram\Reaction\ReactionRemovedCommand;
use App\Application\Command\Telegram\Reaction\StartBanByReactionCommand;
use App\Application\Command\Telegram\Reaction\VoteByReactionCommand;
use App\Application\Command\Telegram\UnsupportedCommand;
use App\Application\Handler\Admin\RequestAdminLinkHandler;
use App\Application\Handler\Ban\StartBanHandler;
use App\Application\Handler\Ban\VoteForBanHandler;
use App\Application\Handler\HelpHandler;
use App\Application\Handler\Reaction\RemoveReactionHandler;
use App\Application\Handler\Reaction\StartBanByReactionHandler;
use App\Application\Handler\Reaction\VoteByReactionHandler;
use App\Application\Handler\UnsupportedHandler;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use Psr\Container\ContainerInterface;

class CommandHandlerFactory
{
    public const array COMMAND_HANDLER_MAP = [
        HelpCommand::class => HelpHandler::class,
        UnsupportedCommand::class => UnsupportedHandler::class,

        StartBanCommand::class => StartBanHandler::class,
        VoteForBanCommand::class => VoteForBanHandler::class,
        StartBanByReactionCommand::class => StartBanByReactionHandler::class,
        VoteByReactionCommand::class => VoteByReactionHandler::class,
        ReactionRemovedCommand::class => RemoveReactionHandler::class,
        RequestAdminLinkCommand::class => RequestAdminLinkHandler::class,
    ];

    public const array COMMAND_REGISTRY_MAP = [
        '/start' => HelpCommand::class,
        '/ban' => StartBanCommand::class,
        '/help' => HelpCommand::class,
        '/admin' => RequestAdminLinkCommand::class,
    ];

    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    public function resolveCommandHandler(string $commandClass): TelegramHandlerInterface
    {
        $handlerClass = self::COMMAND_HANDLER_MAP[$commandClass] ?? null;
        if (!$handlerClass) {
            /** @var TelegramHandlerInterface $handler */
            $handler = $this->container->get(UnsupportedHandler::class);
            return $handler;
        }

        /** @var TelegramHandlerInterface $handler */
        $handler = $this->container->get($handlerClass);
        return $handler;
    }
}
