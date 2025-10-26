<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240727201415 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SEQUENCE "queue_schedule_rule_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql("
            CREATE TABLE public.queue_schedule_rule (
                id BIGINT DEFAULT nextval('queue_schedule_rule_id_seq') NOT NULL,
                schedule VARCHAR(255) NOT NULL, 
                rule VARCHAR(255) NOT NULL, 
                type VARCHAR(255) NOT NULL, 
                message VARCHAR(255) NOT NULL, 
                options JSONB DEFAULT NULL, 
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY(id)
            )
        ");
        $this->addSql('ALTER SEQUENCE queue_schedule_rule_id_seq OWNED BY public.queue_schedule_rule.id');
        $this->addSql('CREATE INDEX idx_queue_schedule_rule_schedule ON public.queue_schedule_rule (schedule)');
        $this->addSql('CREATE INDEX idx_queue_schedule_rule_rule ON public.queue_schedule_rule (rule)');
        $this->addSql('CREATE INDEX idx_queue_schedule_rule_type ON public.queue_schedule_rule (type)');
        $this->addSql('CREATE INDEX idx_queue_schedule_rule_message ON public.queue_schedule_rule (message)');
        $this->addSql('COMMENT ON COLUMN public.queue_schedule_rule.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE IF EXISTS "queue_schedule_rule_id_seq" CASCADE');
        $this->addSql('DROP INDEX IF EXISTS idx_queue_schedule_rule_rule');
        $this->addSql('DROP INDEX IF EXISTS idx_queue_schedule_rule_type');
        $this->addSql('DROP INDEX IF EXISTS idx_queue_schedule_rule_message');
        $this->addSql('DROP TABLE IF EXISTS public.queue_schedule_rule');
    }
}
