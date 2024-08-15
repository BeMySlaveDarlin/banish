<?php

namespace App\Component\Telegram\Repository;

use App\Component\Telegram\Entity\TelegramChatUserBanEntity;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramChatUserBanEntity>
 * @method TelegramChatUserBanEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramChatUserBanEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramChatUserBanEntity[]    findAll()
 * @method TelegramChatUserBanEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramChatUserBanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramChatUserBanEntity::class);
    }

    public function findOldPending(string $status, DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('tcub')
            ->andWhere('tcub.status = :status')
            ->andWhere('tcub.updatedAt <= :updatedAt')
            ->setParameter('status', $status)
            ->setParameter('updatedAt', $date)
            ->getQuery()
            ->getResult();
    }
}
