<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Repository;

use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Enum\BanStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramChatUserBanEntity::class);
    }

    public function findActiveBan(int $chatId, int $banMessageId): ?TelegramChatUserBanEntity
    {
        return $this->findOneBy([
            'chatId' => $chatId,
            'banMessageId' => $banMessageId,
            'status' => BanStatus::PENDING,
        ]);
    }

    public function findByReporterAndMessage(
        int $chatId,
        int $reporterId,
        int $banMessageId
    ): ?TelegramChatUserBanEntity {
        return $this->findOneBy([
            'chatId' => $chatId,
            'reporterId' => $reporterId,
            'banMessageId' => $banMessageId,
        ]);
    }

    public function save(TelegramChatUserBanEntity $ban): void
    {
        $this->getEntityManager()->persist($ban);
        $this->getEntityManager()->flush();
    }

    public function createBan(
        int $chatId,
        int $reporterId,
        int $spammerId,
        int $banMessageId,
        ?int $spamMessageId = null,
        ?int $initialMessageId = null
    ): TelegramChatUserBanEntity {
        $ban = new TelegramChatUserBanEntity();
        $ban->chatId = $chatId;
        $ban->reporterId = $reporterId;
        $ban->spammerId = $spammerId;
        $ban->banMessageId = $banMessageId;
        $ban->spamMessageId = $spamMessageId;
        $ban->initialMessageId = $initialMessageId;
        $ban->status = BanStatus::PENDING;

        return $ban;
    }

    public function findOldPending(BanStatus $status, \DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.status = :status')
            ->andWhere('b.createdAt < :date')
            ->setParameter('status', $status)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
}
