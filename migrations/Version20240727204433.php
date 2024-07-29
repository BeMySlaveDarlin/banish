<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240727204433 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SEQUENCE telegram_chats_users_bans_votes_id_seq");
        $this->addSql("
            CREATE TABLE telegram_chats_users_bans_votes (
                id BIGINT DEFAULT nextval('telegram_chats_users_bans_votes_id_seq') NOT NULL,
                ban_id BIGINT NOT NULL, 
                chat_id BIGINT NOT NULL, 
                user_id BIGINT NOT NULL, 
                vote VARCHAR(16) NOT NULL, 
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("ALTER SEQUENCE telegram_chats_users_bans_votes_id_seq OWNED BY telegram_chats_users_bans_votes.id");
        $this->addSql("COMMENT ON COLUMN telegram_chats_users_bans_votes.created_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql("CREATE INDEX idx_telegram_chats_users_bans_votes_id ON public.telegram_chats_users_bans_votes (id)");
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_votes_ban_id ON public.telegram_chats_users_bans_votes (ban_id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_votes_chat_id ON public.telegram_chats_users_bans_votes (chat_id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_votes_user_id ON public.telegram_chats_users_bans_votes (user_id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_votes_vote ON public.telegram_chats_users_bans_votes (vote)');
        $this->addSql("CREATE INDEX idx_telegram_chats_users_bans_votes_created_at ON public.telegram_chats_users_bans_votes (created_at)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_votes_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_votes_ban_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_votes_chat_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_votes_user_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_votes_vote");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_votes_created_at");
        $this->addSql("DROP TABLE telegram_chats_users_bans_votes");
    }
}
