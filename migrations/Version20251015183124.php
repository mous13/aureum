<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251015183124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add null to stored location';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_lost_property CHANGE stored_location stored_location VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_lost_property CHANGE stored_location stored_location VARCHAR(255) NOT NULL');
    }
}
