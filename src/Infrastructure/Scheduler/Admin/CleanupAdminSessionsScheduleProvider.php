<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler\Admin;

use App\Infrastructure\Scheduler\AbstractScheduleProvider;
use Symfony\Component\Scheduler\Attribute\AsSchedule;

#[AsSchedule('cleanup_admin_sessions')]
class CleanupAdminSessionsScheduleProvider extends AbstractScheduleProvider
{
    protected function getName(): string
    {
        return 'cleanup_admin_sessions';
    }
}
