<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\ValueObject\TelegramUpdate;

class HistoryService
{
    public function __construct(
        private RequestHistoryRepository $requestHistoryRepository,
    ) {
    }

    public function createRequestHistory(TelegramUpdate $update, mixed $result = null): void
    {
        $this->requestHistoryRepository->createHistory(
            $update->getChat()->id,
            $update->getFrom()->id,
            $update->getMessageObj()->message_id,
            $update->update_id,
            $update->request,
            $result
        );
    }
}
