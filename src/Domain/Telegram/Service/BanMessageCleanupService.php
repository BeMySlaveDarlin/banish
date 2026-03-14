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

final readonly class BanMessageCleanupService implements BanMessageCleanupServiceInterface
{
    private const int BATCH_SIZE = 100;
    private const int FINISHED_BANS_DAYS = 7;

    public function __construct(
        private BanRepository $banRepository,
        private TelegramMessageApiInterface $messageApi,
        private LoggerInterface $logger
    ) {
    }

    public function clearBotMessages(): void
    {
        $finishedBans = $this->getFinishedBans();
        $this->clearBans($finishedBans, useCleanup: true);

        $oldPendingBans = $this->getOldPendingBans();
        $this->clearBans($oldPendingBans, useCleanup: false);
    }

    /**
     * @param array<int, TelegramChatUserBanEntity> $bans
     */
    private function clearBans(array $bans, bool $useCleanup): void
    {
        foreach ($bans as $ban) {
            try {
                $banMessageDeleted = true;
                $initialMessageDeleted = true;

                if ($ban->banMessageId > 0) {
                    $banMessageDeleted = $this->messageApi->deleteMessage((int) $ban->chatId, (int) $ban->banMessageId);
                }

                if ($ban->initialMessageId !== null) {
                    $initialMessageDeleted = $this->messageApi->deleteMessage((int) $ban->chatId, (int) $ban->initialMessageId);
                }

                if ($banMessageDeleted && $initialMessageDeleted) {
                    $this->logger->info('Ban messages deleted successfully', [
                        'banId' => $ban->id,
                        'chatId' => $ban->chatId,
                    ]);
                } else {
                    $this->logger->warning('Some ban messages failed to delete', [
                        'banId' => $ban->id,
                        'chatId' => $ban->chatId,
                        'banMessageDeleted' => $banMessageDeleted,
                        'initialMessageDeleted' => $initialMessageDeleted,
                    ]);
                }

                if ($useCleanup) {
                    $ban->markAsCleanedUp();
                } else {
                    $ban->markAsExpired();
                }
                $this->banRepository->save($ban, flush: false);
            } catch (Throwable $e) {
                $this->logger->error('Error clearing ban messages', [
                    'error' => $e->getMessage(),
                    'banId' => $ban->id,
                    'chatId' => $ban->chatId,
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
        $since = (new DateTimeImmutable())->sub(new DateInterval('P' . self::FINISHED_BANS_DAYS . 'D'));

        return $this->banRepository
            ->createQueryBuilder('b')
            ->where('b.status IN (:statuses)')
            ->andWhere('b.createdAt >= :since')
            ->setParameter('statuses', [BanStatus::CANCELED, BanStatus::BANNED])
            ->setParameter('since', $since)
            ->setMaxResults(self::BATCH_SIZE)
            ->getQuery()
            ->getResult();
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
