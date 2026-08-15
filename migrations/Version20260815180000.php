<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'record which employees viewed the modules holding guest contact details';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_logs_access (
            id INT AUTO_INCREMENT NOT NULL,
            hotel_id INT NOT NULL,
            employee_id INT DEFAULT NULL,
            employee_name VARCHAR(100) NOT NULL,
            module VARCHAR(50) NOT NULL,
            method VARCHAR(20) NOT NULL,
            path VARCHAR(255) NOT NULL,
            accessed_at DATETIME NOT NULL,
            INDEX IDX_BDD26ED13243BB18 (hotel_id),
            INDEX IDX_BDD26ED18C03F15C (employee_id),
            INDEX IDX_aureum_logs_access_hotel_at (hotel_id, accessed_at),
            INDEX IDX_aureum_logs_access_at (accessed_at),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE aureum_logs_access ADD CONSTRAINT FK_BDD26ED13243BB18
            FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_logs_access ADD CONSTRAINT FK_BDD26ED18C03F15C
            FOREIGN KEY (employee_id) REFERENCES aureum_employees (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE aureum_logs_access');
    }
}
