<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251007220940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'adds fines and hotel package id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_fines (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, hotel_id INT DEFAULT NULL, number VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, comment VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_9385E22EB03A8386 (created_by_id), INDEX IDX_9385E22E896DBBDE (updated_by_id), INDEX IDX_9385E22E3243BB18 (hotel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aureum_fines ADD CONSTRAINT FK_9385E22EB03A8386 FOREIGN KEY (created_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_fines ADD CONSTRAINT FK_9385E22E896DBBDE FOREIGN KEY (updated_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_fines ADD CONSTRAINT FK_9385E22E3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_packages ADD CONSTRAINT FK_D093720B3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('CREATE INDEX IDX_D093720B3243BB18 ON aureum_packages (hotel_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_fines DROP FOREIGN KEY FK_9385E22EB03A8386');
        $this->addSql('ALTER TABLE aureum_fines DROP FOREIGN KEY FK_9385E22E896DBBDE');
        $this->addSql('ALTER TABLE aureum_fines DROP FOREIGN KEY FK_9385E22E3243BB18');
        $this->addSql('DROP TABLE aureum_fines');
        $this->addSql('ALTER TABLE aureum_packages DROP FOREIGN KEY FK_D093720B3243BB18');
        $this->addSql('DROP INDEX IDX_D093720B3243BB18 ON aureum_packages');
    }
}
