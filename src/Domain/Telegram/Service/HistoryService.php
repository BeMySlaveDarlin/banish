<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

final readonly class HistoryService implements HistoryServiceInterface
{
    public function __construct(
        private RequestHistoryRepository $requestHistoryRepository,
    ) {
    }

    public function createRequestHistory(TelegramUpdate $update, mixed $result = null): void
    {
        $messageId = $update->getMessageId() ?? 0;
        $chatId = $update->getChat()->id ?? 0;
        $fromId = $update->getFrom()->id ?? 0;

        $this->requestHistoryRepository->createHistory(
            $chatId,
            $fromId,
            $messageId,
            $update->update_id,
            $update->request ?? [],
            $result
        );
    }
}
