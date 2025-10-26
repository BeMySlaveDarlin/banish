<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251027100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create admin_action_logs table for audit trail';
    }

    public function up(Schema $schema): void
    {
        $sql = '
            CREATE TABLE admin_action_logs (
                id SERIAL PRIMARY KEY,
                user_id BIGINT NOT NULL,
                chat_id BIGINT NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                data JSON NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                description TEXT
            );
        ';
        $this->addSql($sql);

        $this->addSql('CREATE INDEX idx_admin_action_logs_user_id ON admin_action_logs(user_id)');
        $this->addSql('CREATE INDEX idx_admin_action_logs_chat_id ON admin_action_logs(chat_id)');
        $this->addSql('CREATE INDEX idx_admin_action_logs_action_type ON admin_action_logs(action_type)');
        $this->addSql('CREATE INDEX idx_admin_action_logs_created_at ON admin_action_logs(created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS admin_action_logs');
    }
}
