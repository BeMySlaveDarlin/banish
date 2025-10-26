<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Dispatcher;

use App\Application\Command\Telegram\UnsupportedCommand;
use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Repository\ChatRepository;
use App\Domain\Telegram\Repository\UserRepository;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use App\Infrastructure\Telegram\Middleware\MiddlewareManager;
use App\Infrastructure\Telegram\Routing\Router;

class Dispatcher
{
    public function __construct(
        private readonly Router $router,
        private readonly CommandHandlerFactory $handlerFactory,
        private readonly MiddlewareManager $middlewareManager,
        private readonly ChatRepository $chatRepository,
        private readonly UserRepository $userRepository
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
        $chatId = $update->getChat()->id ?? 0;
        $fromId = $update->getFrom()->id ?? 0;

        if (0 === $chatId || 0 === $fromId) {
            return new UnsupportedCommand();
        }
        $chatType = $update->getChat()->type ?? '';
        $chat = $this->chatRepository->findByChatId($chatId);
        if (null === $chat) {
            $chat = $this->chatRepository->createChat($chatId, $chatType);
            $this->chatRepository->save($chat);
        }

        $user = $this->userRepository->findByChatAndUser($chatId, $fromId);
        if (null === $user) {
            $user = $this->userRepository->createUser($chatId, $fromId);
            $this->userRepository->save($user);
        }

        /** @var TelegramCommandInterface $command */
        $command = new $commandClass($update, $chat, $user);

        return $this->middlewareManager->handle($command);
    }
}
