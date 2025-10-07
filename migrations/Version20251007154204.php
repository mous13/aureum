<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251007154204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'added status to packages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_packages ADD status TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_packages DROP status');
    }
}
