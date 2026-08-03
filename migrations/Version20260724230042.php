<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724230042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add weekly events';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_events (name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, link VARCHAR(255) NOT NULL, id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, hotel_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_377ACBFC8B8E8428 (created_at), INDEX IDX_377ACBFC43625D9F (updated_at), INDEX IDX_377ACBFC3243BB18 (hotel_id), INDEX IDX_377ACBFCB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aureum_events ADD CONSTRAINT FK_377ACBFC3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_events ADD CONSTRAINT FK_377ACBFCB03A8386 FOREIGN KEY (created_by_id) REFERENCES aureum_employees (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_events DROP FOREIGN KEY FK_377ACBFC3243BB18');
        $this->addSql('ALTER TABLE aureum_events DROP FOREIGN KEY FK_377ACBFCB03A8386');
        $this->addSql('DROP TABLE aureum_events');
    }
}
