<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802142127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add floor directory';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_floor_features (type VARCHAR(50) NOT NULL, label VARCHAR(50) NOT NULL, has_steps TINYINT NOT NULL, cells JSON NOT NULL, id INT AUTO_INCREMENT NOT NULL, floor_id INT NOT NULL, INDEX IDX_D0FD8F20854679E2 (floor_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_floors (name VARCHAR(100) NOT NULL, position INT NOT NULL, grid_width INT NOT NULL, grid_height INT NOT NULL, id INT AUTO_INCREMENT NOT NULL, hotel_id INT NOT NULL, INDEX IDX_A39B1BA43243BB18 (hotel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_rooms (number VARCHAR(20) NOT NULL, room_type VARCHAR(50) NOT NULL, bathroom_style VARCHAR(50) NOT NULL, view VARCHAR(50) NOT NULL, bed_size VARCHAR(50) NOT NULL, has_stairs TINYINT NOT NULL, cells JSON NOT NULL, id INT AUTO_INCREMENT NOT NULL, floor_id INT NOT NULL, INDEX IDX_EA956C8F854679E2 (floor_id), UNIQUE INDEX uniq_floor_number (floor_id, number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aureum_floor_features ADD CONSTRAINT FK_D0FD8F20854679E2 FOREIGN KEY (floor_id) REFERENCES aureum_floors (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_floors ADD CONSTRAINT FK_A39B1BA43243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_rooms ADD CONSTRAINT FK_EA956C8F854679E2 FOREIGN KEY (floor_id) REFERENCES aureum_floors (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_floor_features DROP FOREIGN KEY FK_D0FD8F20854679E2');
        $this->addSql('ALTER TABLE aureum_floors DROP FOREIGN KEY FK_A39B1BA43243BB18');
        $this->addSql('ALTER TABLE aureum_rooms DROP FOREIGN KEY FK_EA956C8F854679E2');
        $this->addSql('DROP TABLE aureum_floor_features');
        $this->addSql('DROP TABLE aureum_floors');
        $this->addSql('DROP TABLE aureum_rooms');
    }
}
