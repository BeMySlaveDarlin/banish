<?php

declare(strict_types=1);

namespace App\Domain\Telegram\Repository;

use App\Domain\Telegram\Entity\TelegramChatUserBanEntity;
use App\Domain\Telegram\Enum\BanStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramChatUserBanEntity>
 */
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
        ?int $banMessageId
    ): ?TelegramChatUserBanEntity {
        return $this->findOneBy([
            'chatId' => $chatId,
            'reporterId' => $reporterId,
            'banMessageId' => $banMessageId,
        ]);
    }

    public function findBySpamMessage(int $chatId, ?int $spamMessageId): ?TelegramChatUserBanEntity
    {
        return $this->findOneBy([
            'chatId' => $chatId,
            'spamMessageId' => $spamMessageId,
            'status' => BanStatus::PENDING,
        ]);
    }

    /**
     * @return array<int, TelegramChatUserBanEntity>
     */
    public function findOldPending(BanStatus $status, \DateTimeImmutable $date): array
    {
        return $this
            ->createQueryBuilder('b')
            ->where('b.status = :status')
            ->andWhere('b.createdAt <= :date')
            ->setParameter('status', $status)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    public function save(TelegramChatUserBanEntity $ban, bool $flush = true): void
    {
        $this->getEntityManager()->persist($ban);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TelegramChatUserBanEntity $ban, bool $flush = true): void
    {
        $this->getEntityManager()->remove($ban);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
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

    public function countByChat(int $chatId): int
    {
        return (int) $this
            ->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveBans(int $chatId): int
    {
        return (int) $this
            ->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.chatId = :chatId')
            ->andWhere('b.status = :status')
            ->setParameter('chatId', $chatId)
            ->setParameter('status', BanStatus::PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, TelegramChatUserBanEntity>
     */
    public function findRecent(int $chatId, int $limit = 10): array
    {
        return $this
            ->createQueryBuilder('b')
            ->where('b.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->orderBy('b.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, TelegramChatUserBanEntity>
     */
    public function findRecentWithPagination(int $chatId, int $limit, int $offset): array
    {
        return $this
            ->createQueryBuilder('b')
            ->where('b.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->orderBy('b.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, TelegramChatUserBanEntity>
     */
    public function findActiveBansBySpammer(int $spammerId, int $chatId): array
    {
        return $this
            ->createQueryBuilder('b')
            ->where('b.spammerId = :spammerId')
            ->andWhere('b.chatId = :chatId')
            ->andWhere('b.status IN (:statuses)')
            ->setParameter('spammerId', $spammerId)
            ->setParameter('chatId', $chatId)
            ->setParameter('statuses', [BanStatus::BANNED])
            ->getQuery()
            ->getResult();
    }
}
