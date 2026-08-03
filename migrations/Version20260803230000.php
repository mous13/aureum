<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add platform-wide module announcements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_announcements (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            module VARCHAR(50) NOT NULL,
            severity VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE aureum_announcements');
    }
}
