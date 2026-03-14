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
final class BanRepository extends ServiceEntityRepository
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
    public function findOldPending(BanStatus $status, \DateTimeImmutable $date, int $limit = 100): array
    {
        return $this
            ->createQueryBuilder('b')
            ->where('b.status = :status')
            ->andWhere('b.createdAt <= :date')
            ->setParameter('status', $status)
            ->setParameter('date', $date)
            ->setMaxResults($limit)
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
        return TelegramChatUserBanEntity::create(
            $chatId,
            $reporterId,
            $spammerId,
            $banMessageId,
            $spamMessageId,
            $initialMessageId
        );
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
     * @param array<int, int> $chatIds
     * @return array<int, array{totalBans: int, activeBans: int}>
     */
    public function countByChatsBatch(array $chatIds): array
    {
        if (empty($chatIds)) {
            return [];
        }

        $rows = $this
            ->createQueryBuilder('b')
            ->select(
                'b.chatId',
                'COUNT(b.id) AS totalBans',
                'SUM(CASE WHEN b.status = :pendingStatus THEN 1 ELSE 0 END) AS activeBans'
            )
            ->where('b.chatId IN (:chatIds)')
            ->setParameter('chatIds', $chatIds)
            ->setParameter('pendingStatus', BanStatus::PENDING)
            ->groupBy('b.chatId')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['chatId']] = [
                'totalBans' => (int) $row['totalBans'],
                'activeBans' => (int) $row['activeBans'],
            ];
        }

        return $result;
    }

    /**
     * @param array<int, int> $userIds
     * @return array<int, int>
     */
    public function countActiveBansByUsersBatch(array $userIds, int $chatId): array
    {
        if (empty($userIds)) {
            return [];
        }

        $rows = $this
            ->createQueryBuilder('b')
            ->select('b.spammerId', 'COUNT(b.id) AS bansCount')
            ->where('b.spammerId IN (:userIds)')
            ->andWhere('b.chatId = :chatId')
            ->andWhere('b.status IN (:statuses)')
            ->setParameter('userIds', $userIds)
            ->setParameter('chatId', $chatId)
            ->setParameter('statuses', [BanStatus::BANNED])
            ->groupBy('b.spammerId')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['spammerId']] = (int) $row['bansCount'];
        }

        return $result;
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
