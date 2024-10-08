<?php

namespace App\Component\Telegram\Repository;

use App\Component\Telegram\Entity\TelegramChatUserEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramChatUserEntity>
 * @method TelegramChatUserEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramChatUserEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramChatUserEntity[]    findAll()
 * @method TelegramChatUserEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramChatUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramChatUserEntity::class);
    }

    public function findUserByName(int $chatId, string $name): ?TelegramChatUserEntity
    {
        return $this
            ->createQueryBuilder('u')
            ->where('u.chatId = :chatId')
            ->andWhere('(u.username = :username OR u.name = :username OR u.userId = :username)')
            ->setParameter('chatId', $chatId)
            ->setParameter('username', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
