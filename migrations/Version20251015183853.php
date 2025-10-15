<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251015183853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'adds guest info';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_lost_property ADD guest VARCHAR(255) DEFAULT NULL, ADD contact VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_lost_property DROP guest, DROP contact');
    }
}
