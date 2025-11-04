<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251028110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Populate queue_schedule_rule with scheduler tasks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM queue_schedule_rule WHERE schedule IS NOT NULL');
        $this->addSql(
            "INSERT INTO queue_schedule_rule (schedule, rule, type, message, options, created_at) VALUES
            ('partitions', '0 0 1 * *', 'cron', 'App\Infrastructure\Scheduler\Common\RefreshDbPartitionsMessage', NULL, CURRENT_TIMESTAMP),
            ('clear_bot_messages', '*/2 * * * *', 'cron', 'App\Infrastructure\Scheduler\Telegram\ClearBotMessagesMessage', NULL, CURRENT_TIMESTAMP)"
        );
    }

    public function down(Schema $schema): void
    {
    }
}
