<?php

namespace App\Component\Telegram\Repository;

use App\Component\Common\Entity\ScheduleRuleEntity;
use App\Component\Telegram\Entity\TelegramRequestHistoryEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramRequestHistoryEntity>
 * @method ScheduleRuleEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScheduleRuleEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScheduleRuleEntity[]    findAll()
 * @method ScheduleRuleEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramRequestHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramRequestHistoryEntity::class);
    }

    public function getPreviousMessage(string $chatId, string $messageId): ?TelegramRequestHistoryEntity
    {
        return $this->createQueryBuilder('trh')
            ->where('trh.chatId = :chat_id')
            ->andWhere('trh.messageId < :message_id')
            ->andWhere("JSONB_EXISTS(trh.request, 'callback_query') = false")
            ->setParameter('chat_id', $chatId)
            ->setParameter('message_id', $messageId)
            ->orderBy('trh.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
