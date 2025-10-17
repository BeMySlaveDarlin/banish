<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler\Common;

use App\Infrastructure\Scheduler\AbstractScheduleProvider;
use Symfony\Component\Scheduler\Attribute\AsSchedule;

#[AsSchedule('partitions')]
class PartitionsScheduleProvider extends AbstractScheduleProvider
{
    protected function getName(): string
    {
        return 'partitions';
    }
}
