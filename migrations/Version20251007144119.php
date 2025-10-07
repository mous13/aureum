<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251007144119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add packages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_packages (id INT AUTO_INCREMENT NOT NULL, employee_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, note VARCHAR(255) DEFAULT NULL, INDEX IDX_D093720B8C03F15C (employee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aureum_packages ADD CONSTRAINT FK_D093720B8C03F15C FOREIGN KEY (employee_id) REFERENCES aureum_employees (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_packages DROP FOREIGN KEY FK_D093720B8C03F15C');
        $this->addSql('DROP TABLE aureum_packages');
    }
}
