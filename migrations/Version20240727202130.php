<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use DateInterval;
use DateTime;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240727202130 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SEQUENCE telegram_request_history_id_seq");
        $this->addSql("
            CREATE TABLE telegram_request_history (
                id BIGINT DEFAULT nextval('telegram_request_history_id_seq') NOT NULL,
                chat_id BIGINT NOT NULL,
                from_id BIGINT NOT NULL,
                message_id BIGINT NOT NULL,
                update_id BIGINT NOT NULL,
                request JSONB DEFAULT NULL,
                response JSONB DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
            ) PARTITION BY RANGE (created_at)
        ");
        $this->addSql("ALTER SEQUENCE telegram_request_history_id_seq OWNED BY telegram_request_history.id");
        $this->addSql("COMMENT ON COLUMN telegram_request_history.created_at IS '(DC2Type:datetime_immutable)'");

        $this->addSql("CREATE INDEX idx_telegram_request_history_id ON public.telegram_request_history (id)");
        $this->addSql('CREATE INDEX idx_telegram_request_history_chat_id ON public.telegram_request_history (chat_id)');
        $this->addSql('CREATE INDEX idx_telegram_request_history_from_id ON public.telegram_request_history (from_id)');
        $this->addSql('CREATE INDEX idx_telegram_request_history_message_id ON public.telegram_request_history (message_id)');
        $this->addSql('CREATE INDEX idx_telegram_request_history_update_id ON public.telegram_request_history (update_id)');
        $this->addSql("CREATE INDEX idx_telegram_request_history_created_at ON public.telegram_request_history (created_at)");

        $currentDT = new DateTime('previous month');
        for ($partitionsCount = 1; $partitionsCount <= 4; $partitionsCount++) {
            $currentMonthStart = $currentDT->format('Y-m-01');
            $currentMonthNumber = $currentDT->format('m');
            $currentYearNumber = $currentDT->format('Y');
            $nextMonthStart = $currentDT->add(new DateInterval('P1M'))->format('Y-m-01');
            $this->addSql("
                CREATE TABLE IF NOT EXISTS partitions.telegram_request_history_y{$currentYearNumber}m{$currentMonthNumber}
                    PARTITION OF public.telegram_request_history FOR VALUES FROM ('{$currentMonthStart}') TO ('{$nextMonthStart}');
            ");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_request_history_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_request_history_chat_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_request_history_from_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_request_history_message_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_request_history_update_id");
        $this->addSql("DROP INDEX IF EXISTS idx_telegram_request_history_created_at");
        $this->addSql("DROP TABLE telegram_request_history");
    }
}
