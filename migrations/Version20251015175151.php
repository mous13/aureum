<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251015175151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds lost property';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_lost_property (id INT AUTO_INCREMENT NOT NULL, reported_by_id INT DEFAULT NULL, hotel_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, stored_location VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_3743D24471CE806 (reported_by_id), INDEX IDX_3743D2443243BB18 (hotel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aureum_lost_property ADD CONSTRAINT FK_3743D24471CE806 FOREIGN KEY (reported_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_lost_property ADD CONSTRAINT FK_3743D2443243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_lost_property DROP FOREIGN KEY FK_3743D24471CE806');
        $this->addSql('ALTER TABLE aureum_lost_property DROP FOREIGN KEY FK_3743D2443243BB18');
        $this->addSql('DROP TABLE aureum_lost_property');
    }
}
