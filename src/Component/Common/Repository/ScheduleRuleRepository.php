<?php

namespace App\Component\Common\Repository;

use App\Component\Common\Entity\ScheduleRuleEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduleRuleEntity>
 * @method ScheduleRuleEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScheduleRuleEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScheduleRuleEntity[]    findAll()
 * @method ScheduleRuleEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScheduleRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleRuleEntity::class);
    }
}
