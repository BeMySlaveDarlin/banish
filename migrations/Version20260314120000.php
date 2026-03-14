<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260314120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_bans_chat_status ON public.telegram_chats_users_bans (chat_id, status)');
        $this->addSql('CREATE INDEX idx_bans_chat_spammer_status ON public.telegram_chats_users_bans (chat_id, spammer_user_id, status)');

        $this->addSql('DROP INDEX IF EXISTS idx_telegram_chats_id');
        $this->addSql('DROP INDEX IF EXISTS idx_telegram_chats_users_id');
        $this->addSql('DROP INDEX IF EXISTS idx_telegram_chats_users_bans_id');
        $this->addSql('DROP INDEX IF EXISTS idx_telegram_chats_users_bans_votes_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_bans_chat_status');
        $this->addSql('DROP INDEX IF EXISTS idx_bans_chat_spammer_status');

        $this->addSql('CREATE INDEX idx_telegram_chats_id ON public.telegram_chats (id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_id ON public.telegram_chats_users (id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_id ON public.telegram_chats_users_bans (id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_votes_id ON public.telegram_chats_users_bans_votes (id)');
    }
}
