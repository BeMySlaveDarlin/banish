<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create admin_exchange_tokens table for short-lived exchange tokens';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE admin_exchange_tokens (
            id VARCHAR(36) NOT NULL,
            user_id BIGINT NOT NULL,
            session_id VARCHAR(36) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            used BOOLEAN DEFAULT false NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('COMMENT ON COLUMN admin_exchange_tokens.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN admin_exchange_tokens.expires_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE INDEX idx_admin_exchange_tokens_user_id ON admin_exchange_tokens (user_id)');
        $this->addSql('CREATE INDEX idx_admin_exchange_tokens_expires_at ON admin_exchange_tokens (expires_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS admin_exchange_tokens');
    }
}
