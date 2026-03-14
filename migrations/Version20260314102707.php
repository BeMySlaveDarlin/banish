<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314102707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add UNIQUE constraints on business keys and fix initial_message_id column type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX idx_uniq_chats_chat_id ON telegram_chats (chat_id)');
        $this->addSql('CREATE UNIQUE INDEX idx_uniq_users_chat_user ON telegram_chats_users (chat_id, user_id)');
        $this->addSql('CREATE UNIQUE INDEX idx_uniq_votes_ban_user ON telegram_chats_users_bans_votes (ban_id, user_id)');
        $this->addSql('ALTER TABLE telegram_chats_users_bans ALTER COLUMN initial_message_id DROP DEFAULT');
        $this->addSql('ALTER TABLE telegram_chats_users_bans ALTER COLUMN initial_message_id TYPE BIGINT USING initial_message_id::BIGINT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_uniq_chats_chat_id');
        $this->addSql('DROP INDEX IF EXISTS idx_uniq_users_chat_user');
        $this->addSql('DROP INDEX IF EXISTS idx_uniq_votes_ban_user');
        $this->addSql('ALTER TABLE telegram_chats_users_bans ALTER COLUMN initial_message_id TYPE VARCHAR(255)');
    }
}
