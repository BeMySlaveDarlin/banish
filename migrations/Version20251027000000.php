<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251027000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove messenger tables used for scheduler queues, migrated to RabbitMQ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS messenger_messages;');
    }

    public function down(Schema $schema): void
    {
    }
}
