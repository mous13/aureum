<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251022192831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'added transfer logs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_logs_transfers (id INT AUTO_INCREMENT NOT NULL, transfer_id INT DEFAULT NULL, performed_by_id INT NOT NULL, hotel_id INT NOT NULL, action VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, changes JSON DEFAULT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_B669318C537048AF (transfer_id), INDEX IDX_B669318C2E65C292 (performed_by_id), INDEX IDX_B669318C3243BB18 (hotel_id), INDEX IDX_B669318C3243BB188B8E8428 (hotel_id, created_at), INDEX IDX_B669318C537048AF8B8E8428 (transfer_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aureum_logs_transfers ADD CONSTRAINT FK_B669318C537048AF FOREIGN KEY (transfer_id) REFERENCES aureum_transfers (id)');
        $this->addSql('ALTER TABLE aureum_logs_transfers ADD CONSTRAINT FK_B669318C2E65C292 FOREIGN KEY (performed_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_logs_transfers ADD CONSTRAINT FK_B669318C3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_logs_transfers DROP FOREIGN KEY FK_B669318C537048AF');
        $this->addSql('ALTER TABLE aureum_logs_transfers DROP FOREIGN KEY FK_B669318C2E65C292');
        $this->addSql('ALTER TABLE aureum_logs_transfers DROP FOREIGN KEY FK_B669318C3243BB18');
        $this->addSql('DROP TABLE aureum_logs_transfers');
    }
}
