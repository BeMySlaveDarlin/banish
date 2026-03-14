<?php

declare(strict_types=1);

namespace App\Domain\Admin\Repository;

use App\Domain\Admin\Entity\AdminExchangeTokenEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminExchangeTokenEntity>
 */
class AdminExchangeTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminExchangeTokenEntity::class);
    }

    public function findValidToken(string $tokenId): ?AdminExchangeTokenEntity
    {
        $token = $this->find($tokenId);

        if (!$token || !$token->isValid()) {
            return null;
        }

        return $token;
    }

    public function save(AdminExchangeTokenEntity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AdminExchangeTokenEntity $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function cleanupExpiredTokens(): int
    {
        /** @var int|string|null $result */
        $result = $this
            ->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < CURRENT_TIMESTAMP()')
            ->orWhere('t.used = true')
            ->getQuery()
            ->execute();

        return is_int($result) ? $result : (int) $result;
    }
}
