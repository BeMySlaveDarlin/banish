<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Enum\BanStatus;
use App\Domain\Telegram\Repository\BanRepository;
use DateInterval;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

class BanMessageCleanupService
{
    public function __construct(
        private BanRepository $banRepository,
        private TelegramApiService $telegramApiService,
        private LoggerInterface $logger
    ) {
    }

    public function clearBotMessages(): void
    {
        $finishedBans = $this->getFinishedBans();
        $this->clearBans($finishedBans);

        $oldPendingBans = $this->getOldPendingBans();
        $this->clearBans($oldPendingBans);
    }

    private function clearBans(array $bans): void
    {
        foreach ($bans as $ban) {
            try {
                $this->telegramApiService->deleteMessage($ban->chatId, $ban->banMessageId);

                if ($ban->initialMessageId !== null) {
                    $this->telegramApiService->deleteMessage($ban->chatId, $ban->initialMessageId);
                }
            } catch (Throwable $e) {
                $this->logger->info('Failed to delete ban message', [
                    'error' => $e->getMessage(),
                    'banId' => $ban->id,
                    'chatId' => $ban->chatId,
                ]);
            }

            $ban->status = BanStatus::DELETED;
            $this->banRepository->save($ban);
        }
    }

    /**
     * @return TelegramChatUserBanEntity[]
     */
    private function getFinishedBans(): array
    {
        return $this->banRepository->findBy([
            'status' => [BanStatus::CANCELED, BanStatus::BANNED],
        ]);
    }

    /**
     * @return TelegramChatUserBanEntity[]
     */
    private function getOldPendingBans(): array
    {
        $date = (new DateTimeImmutable())->sub(new DateInterval('PT5M'));

        return $this->banRepository->findOldPending(BanStatus::PENDING, $date);
    }
}
