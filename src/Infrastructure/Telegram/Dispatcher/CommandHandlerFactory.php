<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Dispatcher;

use App\Application\Command\Telegram\Admin\RequestAdminLinkCommand;
use App\Application\Command\Telegram\Ban\StartBanCommand;
use App\Application\Command\Telegram\Ban\VoteForBanCommand;
use App\Application\Command\Telegram\HelpCommand;
use App\Application\Command\Telegram\Message\DeletedMessageCommand;
use App\Application\Command\Telegram\MyChatMember\MyChatMemberCommand;
use App\Application\Command\Telegram\Reaction\ReactionRemovedCommand;
use App\Application\Command\Telegram\Reaction\StartBanByReactionCommand;
use App\Application\Command\Telegram\Reaction\VoteByReactionCommand;
use App\Application\Command\Telegram\UnsupportedCommand;
use App\Application\Handler\Admin\RequestAdminLinkHandler;
use App\Application\Handler\Ban\StartBanHandler;
use App\Application\Handler\Ban\VoteForBanHandler;
use App\Application\Handler\HelpHandler;
use App\Application\Handler\Message\DeletedMessageHandler;
use App\Application\Handler\MyChatMember\MyChatMemberHandler;
use App\Application\Handler\Reaction\RemoveReactionHandler;
use App\Application\Handler\Reaction\StartBanByReactionHandler;
use App\Application\Handler\Reaction\VoteByReactionHandler;
use App\Application\Handler\UnsupportedHandler;
use App\Domain\Telegram\Command\TelegramHandlerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class CommandHandlerFactory
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
        MyChatMemberCommand::class => MyChatMemberHandler::class,
        DeletedMessageCommand::class => DeletedMessageHandler::class,
    ];

    public const array COMMAND_REGISTRY_MAP = [
        '/start' => HelpCommand::class,
        '/ban' => StartBanCommand::class,
        '/help' => HelpCommand::class,
        '/admin' => RequestAdminLinkCommand::class,
    ];

    /** @var array<class-string, class-string<TelegramHandlerInterface>> */
    private readonly array $resolvedHandlerMap;

    /** @var array<string, class-string> */
    private readonly array $resolvedRegistryMap;

    /**
     * @param ServiceLocator<TelegramHandlerInterface> $handlerLocator
     * @param array<class-string, class-string<TelegramHandlerInterface>> $commandHandlerMap
     * @param array<string, class-string> $commandRegistryMap
     */
    public function __construct(
        private readonly ServiceLocator $handlerLocator,
        array $commandHandlerMap = [],
        array $commandRegistryMap = [],
    ) {
        $this->resolvedHandlerMap = $commandHandlerMap !== [] ? $commandHandlerMap : self::COMMAND_HANDLER_MAP;
        $this->resolvedRegistryMap = $commandRegistryMap !== [] ? $commandRegistryMap : self::COMMAND_REGISTRY_MAP;
    }

    public function resolveCommandHandler(string $commandClass): TelegramHandlerInterface
    {
        $handlerClass = $this->resolvedHandlerMap[$commandClass] ?? null;

        if ($handlerClass === null) {
            $handlerClass = UnsupportedHandler::class;
        }

        /** @var TelegramHandlerInterface */
        return $this->handlerLocator->get($handlerClass);
    }

    /** @return array<string, class-string> */
    public function getCommandRegistryMap(): array
    {
        return $this->resolvedRegistryMap;
    }
}
