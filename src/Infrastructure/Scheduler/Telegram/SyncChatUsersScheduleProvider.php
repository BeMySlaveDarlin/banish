<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler\Telegram;

use App\Infrastructure\Scheduler\AbstractScheduleProvider;
use Symfony\Component\Scheduler\Attribute\AsSchedule;

#[AsSchedule('sync_chat_users')]
final class SyncChatUsersScheduleProvider extends AbstractScheduleProvider
{
    protected function getName(): string
    {
        return 'sync_chat_users';
    }
}
