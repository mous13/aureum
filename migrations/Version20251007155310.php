<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251007155310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'added dates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_packages ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_packages DROP created_at, DROP updated_at');
    }
}
