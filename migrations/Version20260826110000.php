<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'allow amenity cards to be marked as priority';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_amenity_cards ADD priority TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_amenity_cards DROP priority');
    }
}
