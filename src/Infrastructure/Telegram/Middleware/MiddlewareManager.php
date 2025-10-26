<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Middleware;

use App\Domain\Telegram\Command\TelegramCommandInterface;

class MiddlewareManager
{
    /**
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(
        private readonly array $middlewares
    ) {
    }

    public function handle(TelegramCommandInterface $command): TelegramCommandInterface
    {
        foreach ($this->middlewares as $middleware) {
            $command = $middleware->handle($command);
        }

        return $command;
    }
}
