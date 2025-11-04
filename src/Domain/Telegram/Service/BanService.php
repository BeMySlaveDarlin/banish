<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatEntity;
use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Enum\BanStatus;
use App\Domain\Telegram\Repository\BanRepository;
use App\Domain\Telegram\Repository\RequestHistoryRepository;
use Psr\Log\LoggerInterface;

class BanService
{
    public function __construct(
        private readonly BanRepository $banRepository,
        private readonly ChatConfigServiceInterface $chatConfigService,
        private readonly TelegramApiService $telegramApiService,
        private readonly RequestHistoryRepository $requestHistoryRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function banUser(TelegramChatEntity $chat, TelegramChatUserBanEntity $ban): void
    {
        $deleteOnlyMessage = $this->chatConfigService->isDeleteOnlyEnabled($chat);
        if (!$deleteOnlyMessage) {
            $ban->status = BanStatus::BANNED;
            $this->telegramApiService->banChatMember($chat->chatId, $ban->spammerId);
        } else {
            $ban->status = BanStatus::CANCELED;
        }

        if ($this->chatConfigService->isDeleteMessagesEnabled($chat)) {
            if ($deleteOnlyMessage && $ban->spamMessageId) {
                $this->deleteMessage($chat->chatId, $ban->spamMessageId);
            } else {
                $this->deleteSpammerMessages($chat->chatId, $ban->spammerId);
            }
        }

        $this->banRepository->save($ban);
    }

    public function forgiveBan(TelegramChatUserBanEntity $ban): void
    {
        $ban->status = BanStatus::CANCELED;
        $this->banRepository->save($ban);
    }

    private function deleteMessage(int $chatId, int $messageId): void
    {
        try {
            $this->telegramApiService->deleteMessage((int) $chatId, (int) $messageId);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to delete spam message', [
                'chatId' => $chatId,
                'messageId' => $messageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deleteSpammerMessages(int $chatId, int $spammerId): void
    {
        $oneHourAgo = (new \DateTimeImmutable())->modify('-1 hour');
        $messageIds = $this->requestHistoryRepository->getMessageIdsByFromId($chatId, $spammerId, $oneHourAgo);

        foreach ($messageIds as $messageId) {
            $this->deleteMessage((int) $chatId, (int) $messageId);
        }
    }
}
