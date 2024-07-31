<?php

namespace App\Component\Telegram\Repository;

use App\Component\Telegram\Entity\TelegramChatUserBanVoteEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramChatUserBanVoteEntity>
 * @method TelegramChatUserBanVoteEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramChatUserBanVoteEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramChatUserBanVoteEntity[]    findAll()
 * @method TelegramChatUserBanVoteEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramChatUserBanVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramChatUserBanVoteEntity::class);
    }
}
