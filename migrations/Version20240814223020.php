<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\Scheduler\Telegram\ClearBotMessagesMessage;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240814223020 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $scheduleRules = [
            ['rule' => '*/2 * * * *', 'schedule' => 'clear_bot_messages', 'type' => 'cron', 'message' => ClearBotMessagesMessage::class],
        ];
        foreach ($scheduleRules as $scheduleRule) {
            $this->addSql('INSERT INTO public.queue_schedule_rule (rule, schedule, type, message) VALUES (:rule, :schedule, :type, :message)', $scheduleRule);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
