<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Component\Common\Schedule\RefreshDbPartitionsMessage;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240727201920 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $scheduleRules = [
            ['rule' => '0 0 1 * *', 'schedule' => 'partitions', 'type' => 'cron', 'message' => RefreshDbPartitionsMessage::class],
        ];
        foreach ($scheduleRules as $scheduleRule) {
            $this->addSql('INSERT INTO public.queue_schedule_rule (rule, schedule, type, message) VALUES (:rule, :schedule, :type, :message)', $scheduleRule);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
