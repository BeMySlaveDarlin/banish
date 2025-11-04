<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Repository\RequestHistoryRepository;
use App\Domain\Telegram\Repository\UserRepository;
use DateTimeImmutable;

class TrustService
{
    private const int TRUST_PERIOD_DAYS = 7;

    public function __construct(
        private readonly RequestHistoryRepository $requestHistoryRepository,
        private readonly ChatConfigServiceInterface $chatConfigService,
        private readonly UserRepository $userRepository
    ) {
    }

    public function isUserTrusted(TelegramChatEntity $chat, int $userId): bool
    {
        if ($this->hasUserBeenInChatForWeek($chat->chatId, $userId)) {
            return true;
        }

        $minMessages = $this->chatConfigService->getMinMessagesForTrust($chat);
        $messageCount = $this->requestHistoryRepository->countMessagesByFromId($chat->chatId, $userId);

        return $messageCount >= $minMessages;
    }

    private function hasUserBeenInChatForWeek(int $chatId, int $userId): bool
    {
        $user = $this->userRepository->findByChatAndUser($chatId, $userId);

        if ($user === null) {
            return false;
        }

        $weekAgo = new DateTimeImmutable('-' . self::TRUST_PERIOD_DAYS . ' days');

        return $user->createdAt <= $weekAgo;
    }
}
