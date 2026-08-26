<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826010358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add sops with categories, audiences, and sign-off records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_sop_categories (
            id INT AUTO_INCREMENT NOT NULL,
            hotel_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            INDEX IDX_850C7E183243BB18 (hotel_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE aureum_sops (
            id INT AUTO_INCREMENT NOT NULL,
            hotel_id INT NOT NULL,
            category_id INT DEFAULT NULL,
            created_by_id INT NOT NULL,
            updated_by_id INT DEFAULT NULL,
            title VARCHAR(150) NOT NULL,
            body LONGTEXT NOT NULL,
            body_text LONGTEXT NOT NULL,
            version INT NOT NULL,
            recheck_months INT DEFAULT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            published_at DATETIME DEFAULT NULL,
            INDEX IDX_C0A892493243BB18 (hotel_id),
            INDEX IDX_C0A8924912469DE2 (category_id),
            INDEX IDX_C0A89249B03A8386 (created_by_id),
            INDEX IDX_C0A89249896DBBDE (updated_by_id),
            INDEX idx_sop_hotel_status (hotel_id, status),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE aureum_sop_audience (
            sop_id INT NOT NULL,
            hotel_role_id INT NOT NULL,
            INDEX IDX_CBD72024D52982EE (sop_id),
            INDEX IDX_CBD720245493C85 (hotel_role_id),
            PRIMARY KEY (sop_id, hotel_role_id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE aureum_sop_sign_offs (
            id INT AUTO_INCREMENT NOT NULL,
            sop_id INT NOT NULL,
            employee_id INT NOT NULL,
            hotel_id INT NOT NULL,
            version INT NOT NULL,
            signed_at DATETIME NOT NULL,
            INDEX IDX_DCC2CE26D52982EE (sop_id),
            INDEX IDX_DCC2CE268C03F15C (employee_id),
            INDEX IDX_DCC2CE263243BB18 (hotel_id),
            UNIQUE INDEX uniq_sop_sign_off (sop_id, employee_id, version),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE aureum_sop_categories ADD CONSTRAINT FK_850C7E183243BB18
            FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_sops ADD CONSTRAINT FK_C0A892493243BB18
            FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_sops ADD CONSTRAINT FK_C0A8924912469DE2
            FOREIGN KEY (category_id) REFERENCES aureum_sop_categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE aureum_sops ADD CONSTRAINT FK_C0A89249B03A8386
            FOREIGN KEY (created_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_sops ADD CONSTRAINT FK_C0A89249896DBBDE
            FOREIGN KEY (updated_by_id) REFERENCES aureum_employees (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE aureum_sop_audience ADD CONSTRAINT FK_CBD72024D52982EE
            FOREIGN KEY (sop_id) REFERENCES aureum_sops (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_sop_audience ADD CONSTRAINT FK_CBD720245493C85
            FOREIGN KEY (hotel_role_id) REFERENCES aureum_hotel_roles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_sop_sign_offs ADD CONSTRAINT FK_DCC2CE26D52982EE
            FOREIGN KEY (sop_id) REFERENCES aureum_sops (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_sop_sign_offs ADD CONSTRAINT FK_DCC2CE268C03F15C
            FOREIGN KEY (employee_id) REFERENCES aureum_employees (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_sop_sign_offs ADD CONSTRAINT FK_DCC2CE263243BB18
            FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE aureum_sop_sign_offs');
        $this->addSql('DROP TABLE aureum_sop_audience');
        $this->addSql('DROP TABLE aureum_sops');
        $this->addSql('DROP TABLE aureum_sop_categories');
    }
}
