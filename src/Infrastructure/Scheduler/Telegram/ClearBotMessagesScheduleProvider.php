<?php

declare(strict_types=1);

namespace App\Infrastructure\Scheduler\Telegram;

use App\Infrastructure\Scheduler\AbstractScheduleProvider;
use Symfony\Component\Scheduler\Attribute\AsSchedule;

#[AsSchedule('clear_bot_messages')]
class ClearBotMessagesScheduleProvider extends AbstractScheduleProvider
{
    protected function getName(): string
    {
        return 'clear_bot_messages';
    }
}
