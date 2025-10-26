<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251026180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create admin_sessions table for admin panel access tokens';
    }

    public function up(Schema $schema): void
    {
        $sql = '
            CREATE TABLE admin_sessions (
                id VARCHAR(36) PRIMARY KEY,
                user_id BIGINT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL
            );
        ';
        $this->addSql($sql);

        $this->addSql('CREATE INDEX idx_admin_sessions_user_id ON admin_sessions(user_id)');
        $this->addSql('CREATE INDEX idx_admin_sessions_expires_at ON admin_sessions(expires_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS admin_sessions');
    }
}
