<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240920211120 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE public.telegram_chats_users_bans ALTER COLUMN spam_message_id DROP NOT NULL;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE public.telegram_chats_users_bans ALTER COLUMN spam_message_id SET NOT NULL;");
    }
}
