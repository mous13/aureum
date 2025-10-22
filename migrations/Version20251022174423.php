<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251022174423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'changes status from bool to enum';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_packages CHANGE status status VARCHAR(255) NOT NULL');
        $this->addSql("UPDATE aureum_packages SET status = 'picked_up' WHERE status = '0'");
        $this->addSql("UPDATE aureum_packages SET status = 'received' WHERE status = '1'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE aureum_packages SET status = 0 WHERE status = 'picked_up'");
        $this->addSql("UPDATE aureum_packages SET status = 1 WHERE status = 'received'");
        $this->addSql('ALTER TABLE aureum_packages CHANGE status status TINYINT(1) DEFAULT 1 NOT NULL');
    }
}
