<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241118225812 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE public.telegram_chats_users_bans ADD COLUMN initial_message_id VARCHAR(255) DEFAULT NULL;"
        );
        $this->addSql(
            "CREATE INDEX idx_telegram_chats_users_bans_initial_message_id ON public.telegram_chats_users_bans (initial_message_id);"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_initial_message_id");
        $this->addSql("ALTER TABLE ublic.telegram_chats_users_bans DROP COLUMN initial_message_id;");
    }
}
