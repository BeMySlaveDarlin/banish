<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add version column for optimistic locking and partial unique index on bans';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE telegram_chats_users_bans ADD COLUMN version INTEGER NOT NULL DEFAULT 1');

        $this->addSql(
            'CREATE UNIQUE INDEX idx_uniq_bans_pending_spam
            ON telegram_chats_users_bans (chat_id, spam_message_id)
            WHERE status = \'pending\' AND spam_message_id IS NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_uniq_bans_pending_spam');
        $this->addSql('ALTER TABLE telegram_chats_users_bans DROP COLUMN version');
    }
}
