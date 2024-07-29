<?php

declare(strict_types=1);

namespace App\Component\Common\Schedule;

use App\Service\Scheduler\Policy\AbstractScheduleProvider;
use Symfony\Component\Scheduler\Attribute\AsSchedule;

#[AsSchedule('partitions')]
class PartitionsScheduleProvider extends AbstractScheduleProvider
{
    protected function getName(): string
    {
        return 'partitions';
    }
}
