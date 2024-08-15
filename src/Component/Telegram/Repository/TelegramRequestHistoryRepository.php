<?php

namespace App\Component\Telegram\Repository;

use App\Component\Telegram\Entity\TelegramRequestHistoryEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramRequestHistoryEntity>
 * @method TelegramRequestHistoryEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramRequestHistoryEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramRequestHistoryEntity[]    findAll()
 * @method TelegramRequestHistoryEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramRequestHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramRequestHistoryEntity::class);
    }

    public function findPreviousMessage(int $chatId, int $fromId, int $messageId): ?TelegramRequestHistoryEntity
    {
        return $this->createQueryBuilder('trh')
            ->andWhere('trh.chatId = :chat_id')
            ->andWhere('trh.fromId != :from_id')
            ->andWhere('trh.messageId < :message_id')
            ->andWhere("JSONB_EXISTS(trh.request, 'callback_query') = false")
            ->setParameter('chat_id', $chatId)
            ->setParameter('from_id', $fromId)
            ->setParameter('message_id', $messageId)
            ->orderBy('trh.message_id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
