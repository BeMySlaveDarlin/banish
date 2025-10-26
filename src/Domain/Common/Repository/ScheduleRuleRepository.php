<?php

declare(strict_types=1);

namespace App\Domain\Common\Repository;

use App\Domain\Common\Entity\ScheduleRuleEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduleRuleEntity>
 */
class ScheduleRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleRuleEntity::class);
    }
}
