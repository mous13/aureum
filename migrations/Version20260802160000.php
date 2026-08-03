<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'rename rooms view column to room_view, make feature label nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_rooms CHANGE `view` room_view VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE aureum_floor_features CHANGE label label VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_rooms CHANGE room_view `view` VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE aureum_floor_features CHANGE label label VARCHAR(50) NOT NULL');
    }
}
