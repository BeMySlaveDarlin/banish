<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change admin_action_logs.data from JSON to JSONB, add GIN index on telegram_request_history.request';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_action_logs ALTER COLUMN data TYPE JSONB USING data::JSONB');

        $this->addSql('CREATE INDEX idx_request_history_request_gin ON telegram_request_history USING gin (request)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_request_history_request_gin');

        $this->addSql('ALTER TABLE admin_action_logs ALTER COLUMN data TYPE JSON USING data::JSON');
    }
}
