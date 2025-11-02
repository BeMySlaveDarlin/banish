<?php

declare(strict_types=1);

namespace App\Domain\Admin\Repository;

use App\Domain\Admin\Entity\AdminActionLogEntity;
use App\Domain\Admin\Enum\AdminActionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminActionLogEntity>
 */
class AdminActionLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminActionLogEntity::class);
    }

    /**
     * @return AdminActionLogEntity[]
     */
    public function findByChat(int $chatId, int $limit = 50): array
    {
        return $this
            ->createQueryBuilder('log')
            ->where('log.chatId = :chatId')
            ->setParameter('chatId', $chatId)
            ->orderBy('log.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AdminActionLogEntity[]
     */
    public function findByUser(int $userId, int $limit = 50): array
    {
        return $this
            ->createQueryBuilder('log')
            ->where('log.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('log.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AdminActionLogEntity[]
     */
    public function findByChatAndUser(int $chatId, int $userId, int $limit = 50): array
    {
        return $this
            ->createQueryBuilder('log')
            ->where('log.chatId = :chatId')
            ->andWhere('log.userId = :userId')
            ->setParameter('chatId', $chatId)
            ->setParameter('userId', $userId)
            ->orderBy('log.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AdminActionLogEntity[]
     */
    public function findByType(AdminActionType $actionType, int $limit = 50): array
    {
        return $this
            ->createQueryBuilder('log')
            ->where('log.actionType = :actionType')
            ->setParameter('actionType', $actionType)
            ->orderBy('log.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(AdminActionLogEntity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
