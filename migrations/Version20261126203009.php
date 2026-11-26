<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261126203009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add streetle maps, streets and sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_streetle_maps (west DOUBLE PRECISION NOT NULL, south DOUBLE PRECISION NOT NULL, east DOUBLE PRECISION NOT NULL, north DOUBLE PRECISION NOT NULL, basemap JSON NOT NULL, street_count INT NOT NULL, imported_at DATETIME NOT NULL, id INT AUTO_INCREMENT NOT NULL, hotel_id INT NOT NULL, UNIQUE INDEX UNIQ_658FFC053243BB18 (hotel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_streetle_sessions (mode VARCHAR(20) NOT NULL, started_at DATETIME NOT NULL, last_activity_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, guess_count INT NOT NULL, correct_count INT NOT NULL, named_street_ids JSON NOT NULL, revealed_street_ids JSON NOT NULL, id INT AUTO_INCREMENT NOT NULL, hotel_id INT NOT NULL, employee_id INT NOT NULL, INDEX idx_streetle_session_board (hotel_id, mode, ended_at), INDEX IDX_7BA93563243BB18 (hotel_id), INDEX IDX_7BA93568C03F15C (employee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_streetle_streets (name VARCHAR(255) NOT NULL, normalized_name VARCHAR(255) NOT NULL, kind VARCHAR(10) NOT NULL, shape JSON NOT NULL, id INT AUTO_INCREMENT NOT NULL, map_id INT NOT NULL, INDEX idx_streetle_street_name (map_id, normalized_name), INDEX IDX_9B8F91BB53C55F64 (map_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aureum_streetle_maps ADD CONSTRAINT FK_658FFC053243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_streetle_sessions ADD CONSTRAINT FK_7BA93563243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_streetle_sessions ADD CONSTRAINT FK_7BA93568C03F15C FOREIGN KEY (employee_id) REFERENCES aureum_employees (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_streetle_streets ADD CONSTRAINT FK_9B8F91BB53C55F64 FOREIGN KEY (map_id) REFERENCES aureum_streetle_maps (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_streetle_streets DROP FOREIGN KEY FK_9B8F91BB53C55F64');
        $this->addSql('ALTER TABLE aureum_streetle_sessions DROP FOREIGN KEY FK_7BA93568C03F15C');
        $this->addSql('ALTER TABLE aureum_streetle_sessions DROP FOREIGN KEY FK_7BA93563243BB18');
        $this->addSql('ALTER TABLE aureum_streetle_maps DROP FOREIGN KEY FK_658FFC053243BB18');
        $this->addSql('DROP TABLE aureum_streetle_streets');
        $this->addSql('DROP TABLE aureum_streetle_sessions');
        $this->addSql('DROP TABLE aureum_streetle_maps');
    }
}
