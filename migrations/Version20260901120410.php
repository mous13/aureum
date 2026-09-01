<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901120410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'restaurant opening times and hotel google places settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_hotels ADD timezone VARCHAR(64) DEFAULT NULL, ADD google_places_enabled TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE aureum_restaurants ADD opening_times JSON DEFAULT NULL, ADD google_place_id VARCHAR(255) DEFAULT NULL, ADD opening_times_synced_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_hotels DROP timezone, DROP google_places_enabled');
        $this->addSql('ALTER TABLE aureum_restaurants DROP opening_times, DROP google_place_id, DROP opening_times_synced_at');
    }
}
