<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Dispatcher;

use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\ValueObject\TelegramUpdate;
use App\Infrastructure\Telegram\Middleware\MiddlewareManager;
use App\Infrastructure\Telegram\Routing\Router;

class Dispatcher
{
    public function __construct(
        private Router $router,
        private HandlerFactory $handlerFactory,
        private MiddlewareManager $middlewareManager,
    ) {
    }

    public function dispatch(TelegramUpdate $update): string
    {
        $commandClass = $this->router->route($update);

        /** @var TelegramCommandInterface $command */
        $command = new $commandClass($update);
        $command = $this->middlewareManager->handle($command);

        return $this->handlerFactory->resolveCommandHandler($commandClass)->handle($command);
    }
}
