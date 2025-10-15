<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251015202747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add lost property log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_logs_lost_property (id INT AUTO_INCREMENT NOT NULL, lost_property_id INT NOT NULL, performed_by_id INT NOT NULL, hotel_id INT NOT NULL, action VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, changes JSON DEFAULT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_327D244AF9D77495 (lost_property_id), INDEX IDX_327D244A2E65C292 (performed_by_id), INDEX IDX_327D244A3243BB18 (hotel_id), INDEX IDX_327D244A3243BB188B8E8428 (hotel_id, created_at), INDEX IDX_327D244AF9D774958B8E8428 (lost_property_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aureum_logs_lost_property ADD CONSTRAINT FK_327D244AF9D77495 FOREIGN KEY (lost_property_id) REFERENCES aureum_lost_property (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_logs_lost_property ADD CONSTRAINT FK_327D244A2E65C292 FOREIGN KEY (performed_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_logs_lost_property ADD CONSTRAINT FK_327D244A3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_logs_lost_property DROP FOREIGN KEY FK_327D244AF9D77495');
        $this->addSql('ALTER TABLE aureum_logs_lost_property DROP FOREIGN KEY FK_327D244A2E65C292');
        $this->addSql('ALTER TABLE aureum_logs_lost_property DROP FOREIGN KEY FK_327D244A3243BB18');
        $this->addSql('DROP TABLE aureum_logs_lost_property');
    }
}
