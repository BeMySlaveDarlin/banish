<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Repository\RequestHistoryRepository;

class TrustService
{
    public function __construct(
        private readonly RequestHistoryRepository $requestHistoryRepository,
        private readonly ChatConfigServiceInterface $chatConfigService
    ) {
    }

    public function isUserTrusted(TelegramChatEntity $chat, int $userId): bool
    {
        $minMessages = $this->chatConfigService->getMinMessagesForTrust($chat);
        $messageCount = $this->requestHistoryRepository->countMessagesByFromId($chat->chatId, $userId);

        return $messageCount >= $minMessages;
    }
}
