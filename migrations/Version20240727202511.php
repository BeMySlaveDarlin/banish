<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240727202511 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SEQUENCE telegram_chats_id_seq");
        $this->addSql("
            CREATE TABLE telegram_chats (
                id BIGINT DEFAULT nextval('telegram_chats_id_seq') NOT NULL,
                chat_id BIGINT NOT NULL, 
                type VARCHAR(255) NOT NULL, 
                name TEXT DEFAULT NULL, 
                is_enabled BOOLEAN NOT NULL DEFAULT FALSE, 
                options JSONB DEFAULT NULL, 
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("ALTER SEQUENCE telegram_chats_id_seq OWNED BY telegram_chats.id");
        $this->addSql("COMMENT ON COLUMN telegram_chats.created_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql("CREATE INDEX idx_telegram_chats_id ON public.telegram_chats (id)");
        $this->addSql('CREATE INDEX idx_telegram_chats_chat_id ON public.telegram_chats (chat_id)');
        $this->addSql('CREATE INDEX idx_telegram_chats_type ON public.telegram_chats (type)');
        $this->addSql("CREATE INDEX idx_telegram_chats_created_at ON public.telegram_chats (created_at)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_chat_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_type");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_chats_created_at");
        $this->addSql("DROP TABLE telegram_chats");
    }
}
