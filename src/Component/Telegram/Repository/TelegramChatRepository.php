<?php

namespace App\Component\Telegram\Repository;

use App\Component\Common\Entity\ScheduleRuleEntity;
use App\Component\Telegram\Entity\TelegramChatEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramChatEntity>
 * @method ScheduleRuleEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScheduleRuleEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScheduleRuleEntity[]    findAll()
 * @method ScheduleRuleEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramChatEntity::class);
    }
}
