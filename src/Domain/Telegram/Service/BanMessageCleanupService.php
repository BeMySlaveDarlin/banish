<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Service;

use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Enum\BanStatus;
use App\Domain\Telegram\Repository\BanRepository;
use DateInterval;
use DateInvalidOperationException;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

class BanMessageCleanupService
{
    public function __construct(
        private readonly BanRepository $banRepository,
        private readonly TelegramApiService $telegramApiService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function clearBotMessages(): void
    {
        $finishedBans = $this->getFinishedBans();
        $this->clearBans($finishedBans);

        $oldPendingBans = $this->getOldPendingBans();
        $this->clearBans($oldPendingBans);
    }

    /**
     * @param array<int, TelegramChatUserBanEntity> $bans
     */
    private function clearBans(array $bans): void
    {
        foreach ($bans as $ban) {
            try {
                $banMessageDeleted = true;
                $initialMessageDeleted = true;

                if ($ban->banMessageId > 0) {
                    $banMessageDeleted = $this->telegramApiService->deleteMessage((int) $ban->chatId, (int) $ban->banMessageId);
                }

                if ($ban->initialMessageId !== null) {
                    $initialMessageDeleted = $this->telegramApiService->deleteMessage((int) $ban->chatId, (int) $ban->initialMessageId);
                }

                if ($banMessageDeleted && $initialMessageDeleted) {
                    $this->logger->info('Ban messages deleted successfully', [
                        'banId' => $ban->id,
                        'chatId' => $ban->chatId,
                        'banMessageId' => $ban->banMessageId,
                        'initialMessageId' => $ban->initialMessageId,
                    ]);
                } else {
                    $this->logger->warning('Some ban messages failed to delete', [
                        'banId' => $ban->id,
                        'chatId' => $ban->chatId,
                        'banMessageDeleted' => $banMessageDeleted,
                        'initialMessageDeleted' => $initialMessageDeleted,
                    ]);
                }

                $ban->status = BanStatus::DELETED;
                $this->banRepository->save($ban, flush: false);
            } catch (Throwable $e) {
                $this->logger->error('Error clearing ban messages', [
                    'error' => $e->getMessage(),
                    'banId' => $ban->id,
                    'chatId' => $ban->chatId,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
        $this->banRepository->flush();
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
     * @throws DateInvalidOperationException
     */
    private function getOldPendingBans(): array
    {
        $date = (new DateTimeImmutable())->sub(new DateInterval('PT10M'));

        return $this->banRepository->findOldPending(BanStatus::PENDING, $date);
    }
}
