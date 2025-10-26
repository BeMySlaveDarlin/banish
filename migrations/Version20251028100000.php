<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251028100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status field to telegram_chats_users for tracking user state';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE telegram_chats_users ADD COLUMN status VARCHAR(255) DEFAULT \'active\' NOT NULL');
        $this->addSql('COMMENT ON COLUMN telegram_chats_users.status IS \'(DC2Type:UserStatus)\'');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_status ON telegram_chats_users(status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_telegram_chats_users_status');
        $this->addSql('ALTER TABLE telegram_chats_users DROP COLUMN status');
    }
}
