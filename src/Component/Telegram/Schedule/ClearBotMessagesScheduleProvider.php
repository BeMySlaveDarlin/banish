<?php

declare(strict_types=1);

namespace App\Component\Telegram\Schedule;

use App\Service\Scheduler\Policy\AbstractScheduleProvider;
use Symfony\Component\Scheduler\Attribute\AsSchedule;

#[AsSchedule('clear_bot_messages')]
class ClearBotMessagesScheduleProvider extends AbstractScheduleProvider
{
    protected function getName(): string
    {
        return 'clear_bot_messages';
    }
}
