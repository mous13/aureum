<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251022171716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'added package logs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_logs_packages (id INT AUTO_INCREMENT NOT NULL, package_id INT NOT NULL, performed_by_id INT NOT NULL, hotel_id INT NOT NULL, action VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, changes JSON DEFAULT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_DBF7A20DF44CABFF (package_id), INDEX IDX_DBF7A20D2E65C292 (performed_by_id), INDEX IDX_DBF7A20D3243BB18 (hotel_id), INDEX IDX_DBF7A20D3243BB188B8E8428 (hotel_id, created_at), INDEX IDX_DBF7A20DF44CABFF8B8E8428 (package_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aureum_logs_packages ADD CONSTRAINT FK_DBF7A20DF44CABFF FOREIGN KEY (package_id) REFERENCES aureum_packages (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_logs_packages ADD CONSTRAINT FK_DBF7A20D2E65C292 FOREIGN KEY (performed_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_logs_packages ADD CONSTRAINT FK_DBF7A20D3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_logs_packages DROP FOREIGN KEY FK_DBF7A20DF44CABFF');
        $this->addSql('ALTER TABLE aureum_logs_packages DROP FOREIGN KEY FK_DBF7A20D2E65C292');
        $this->addSql('ALTER TABLE aureum_logs_packages DROP FOREIGN KEY FK_DBF7A20D3243BB18');
        $this->addSql('DROP TABLE aureum_logs_packages');
    }
}
