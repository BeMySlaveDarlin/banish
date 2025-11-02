<?php

declare(strict_types=1);

namespace App\Domain\Admin\Repository;

use App\Domain\Admin\Entity\AdminSessionEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminSessionEntity>
 */
class AdminSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminSessionEntity::class);
    }

    public function findValidSession(string $token): ?AdminSessionEntity
    {
        $session = $this->find($token);

        if (!$session || $session->isExpired()) {
            return null;
        }

        return $session;
    }

    /**
     * @return AdminSessionEntity[]
     */
    public function findActiveByUser(int $userId): array
    {
        return $this
            ->createQueryBuilder('s')
            ->where('s.userId = :userId')
            ->andWhere('s.expiresAt > CURRENT_TIMESTAMP()')
            ->setParameter('userId', $userId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function cleanupExpiredSessions(): int
    {
        /** @var int|string|null $result */
        $result = $this
            ->createQueryBuilder('s')
            ->delete()
            ->where('s.expiresAt < CURRENT_TIMESTAMP()')
            ->getQuery()
            ->execute();

        return is_int($result) ? $result : (int) $result;
    }

    public function save(AdminSessionEntity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AdminSessionEntity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
