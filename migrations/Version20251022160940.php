<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251022160940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add fine logs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_logs_fines (id INT AUTO_INCREMENT NOT NULL, fine_id INT NOT NULL, performed_by_id INT NOT NULL, hotel_id INT NOT NULL, action VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, changes JSON DEFAULT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_B5B31282E90B2A0C (fine_id), INDEX IDX_B5B312822E65C292 (performed_by_id), INDEX IDX_B5B312823243BB18 (hotel_id), INDEX IDX_B5B312823243BB188B8E8428 (hotel_id, created_at), INDEX IDX_B5B31282E90B2A0C8B8E8428 (fine_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aureum_logs_fines ADD CONSTRAINT FK_B5B31282E90B2A0C FOREIGN KEY (fine_id) REFERENCES aureum_fines (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_logs_fines ADD CONSTRAINT FK_B5B312822E65C292 FOREIGN KEY (performed_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_logs_fines ADD CONSTRAINT FK_B5B312823243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_logs_fines DROP FOREIGN KEY FK_B5B31282E90B2A0C');
        $this->addSql('ALTER TABLE aureum_logs_fines DROP FOREIGN KEY FK_B5B312822E65C292');
        $this->addSql('ALTER TABLE aureum_logs_fines DROP FOREIGN KEY FK_B5B312823243BB18');
        $this->addSql('DROP TABLE aureum_logs_fines');
    }
}
