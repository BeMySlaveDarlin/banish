<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Dispatcher;

use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Service\ChatPersister;
use App\Domain\Telegram\Service\UserPersister;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use App\Infrastructure\Telegram\Middleware\MiddlewareManager;
use App\Infrastructure\Telegram\Routing\Router;

class Dispatcher
{
    public function __construct(
        private readonly Router $router,
        private readonly CommandHandlerFactory $handlerFactory,
        private readonly MiddlewareManager $middlewareManager,
        private readonly ChatPersister $chatPersister,
        private readonly UserPersister $userPersister
    ) {
    }

    public function dispatch(TelegramUpdate $update): string
    {
        $commandClass = $this->router->route($update);
        $command = $this->getCommand($update, $commandClass);

        return $this->handlerFactory->resolveCommandHandler($commandClass)->handle($command);
    }

    private function getCommand(TelegramUpdate $update, string $commandClass): UnsupportedCommand | TelegramCommandInterface
    {
        $tgChat = $update->getChat();
        $tgUser = $update->getFrom();
        if (!$tgChat?->id || !$tgUser?->id) {
            return new UnsupportedCommand();
        }

        $chat = $this->chatPersister->persist($tgChat);
        $user = $this->userPersister->persist($tgChat, $tgUser);

        /** @var TelegramCommandInterface $command */
        $command = new $commandClass($update, $chat, $user);

        return $this->middlewareManager->handle($command);
    }
}
