<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251028000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at field to telegram_request_history for tracking deleted messages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE telegram_request_history ADD COLUMN deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN telegram_request_history.deleted_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE telegram_request_history DROP COLUMN deleted_at');
    }
}
