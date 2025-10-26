<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240727201520 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE SCHEMA IF NOT EXISTS partitions;");
    }

    public function down(Schema $schema): void
    {
        $this->write('Warning: Schema deleting is dangerous. If necessary, please down it manually.');
    }
}
