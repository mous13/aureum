<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'replace employee role enum with a hotel_admin flag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_employees ADD hotel_admin TINYINT NOT NULL');
        $this->addSql("UPDATE aureum_employees SET hotel_admin = 1 WHERE role = 'Manager'");
        $this->addSql('ALTER TABLE aureum_employees DROP role');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE aureum_employees ADD role VARCHAR(255) NOT NULL DEFAULT 'Concierge'");
        $this->addSql("UPDATE aureum_employees SET role = 'Manager' WHERE hotel_admin = 1");
        $this->addSql('ALTER TABLE aureum_employees DROP hotel_admin');
    }
}
