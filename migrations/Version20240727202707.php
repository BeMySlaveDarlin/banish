<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240727202707 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SEQUENCE telegram_chats_users_id_seq");
        $this->addSql("
            CREATE TABLE telegram_chats_users (
                id BIGINT DEFAULT nextval('telegram_chats_users_id_seq') NOT NULL,
                chat_id BIGINT NOT NULL, 
                user_id BIGINT NOT NULL, 
                username VARCHAR(255) DEFAULT NULL, 
                name TEXT DEFAULT NULL, 
                is_admin BOOLEAN NOT NULL DEFAULT FALSE, 
                is_bot BOOLEAN NOT NULL DEFAULT FALSE, 
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("ALTER SEQUENCE telegram_chats_users_id_seq OWNED BY telegram_chats_users.id");
        $this->addSql("COMMENT ON COLUMN telegram_chats_users.created_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql("CREATE INDEX idx_telegram_chats_users_id ON public.telegram_chats_users (id)");
        $this->addSql('CREATE INDEX idx_telegram_chats_users_chat_id ON public.telegram_chats_users (chat_id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_user_id ON public.telegram_chats_users (user_id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_is_admin ON public.telegram_chats_users (is_admin)');
        $this->addSql('CREATE INDEX idx_telegram_chats_users_is_bot ON public.telegram_chats_users (is_bot)');
        $this->addSql("CREATE INDEX idx_telegram_chats_users_created_at ON public.telegram_chats_users (created_at)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_chat_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_user_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_is_admin");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_is_bot");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_users_created_at");
        $this->addSql("DROP TABLE telegram_chats_users");
    }
}
