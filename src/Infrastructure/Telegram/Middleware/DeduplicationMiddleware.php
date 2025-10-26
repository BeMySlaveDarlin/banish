<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\Middleware;

use App\Domain\Telegram\Command\TelegramCommandInterface;
use App\Domain\Telegram\Exception\DuplicateUpdateException;
use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

class DeduplicationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RequestHistoryRepository $requestHistoryRepository
    ) {
    }

    public function handle(TelegramCommandInterface $command): TelegramCommandInterface
    {
        /** @var TelegramUpdate|null $update */
        $update = $command->update ?? null;
        if (!$update instanceof TelegramUpdate) {
            return $command;
        }

        $chatId = $update->getChat()->id ?? 0;
        $fromId = $update->getFrom()->id ?? 0;
        $messageId = $update->getMessageId() ?? 0;
        $updateId = $update->update_id;

        $existing = $this->requestHistoryRepository->findByUpdate(
            $chatId,
            $fromId,
            $messageId,
            $updateId
        );

        if ($existing !== null) {
            throw new DuplicateUpdateException($updateId);
        }

        return $command;
    }
}
