<?php

namespace App\Component\Telegram\Repository;

use App\Component\Telegram\Entity\TelegramChatEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramChatEntity>
 * @method TelegramChatEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramChatEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramChatEntity[]    findAll()
 * @method TelegramChatEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramChatEntity::class);
    }
}
