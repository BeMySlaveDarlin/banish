<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Middleware;

use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Repository\RequestHistoryRepository;

class DeduplicationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RequestHistoryRepository $requestHistoryRepository
    ) {
    }

    public function handle(TelegramCommandInterface $command): TelegramCommandInterface
    {
        return $command;
    }
}
