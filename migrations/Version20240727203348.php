<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240727203348 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SEQUENCE telegram_chats_users_bans_id_seq");
        $this->addSql("
            CREATE TABLE telegram_chats_users_bans (
                id BIGINT DEFAULT nextval('telegram_chats_users_bans_id_seq') NOT NULL,
                chat_id BIGINT NOT NULL, 
                ban_message_id BIGINT NOT NULL, 
                spam_message_id BIGINT NOT NULL, 
                spammer_user_id BIGINT NOT NULL, 
                reporter_user_id BIGINT NOT NULL, 
                status VARCHAR(255) NOT NULL, 
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("ALTER SEQUENCE telegram_chats_users_bans_id_seq OWNED BY telegram_chats_users_bans.id");
        $this->addSql("COMMENT ON COLUMN telegram_chats_users_bans.created_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql("CREATE INDEX idx_telegram_chats_users_bans_id ON public.telegram_chats_users_bans (id)");
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_chat_id ON public.telegram_chats_users_bans (chat_id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_ban_message_id ON public.telegram_chats_users_bans (ban_message_id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_spam_message_id ON public.telegram_chats_users_bans (spam_message_id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_spammer_user_id ON public.telegram_chats_users_bans (spammer_user_id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_reporter_user_id ON public.telegram_chats_users_bans (reporter_user_id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_bans_status ON public.telegram_chats_users_bans (status)');
        $this->addSql("CREATE INDEX idx_telegram_chats_users_bans_created_at ON public.telegram_chats_users_bans (created_at)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_chat_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_ban_message_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_spam_message_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_spammer_user_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_reporter_user_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_status");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_bans_created_at");
        $this->addSql("DROP TABLE telegram_chats_users_bans");
    }
}
