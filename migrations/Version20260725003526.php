<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725003526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'make link and description nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_events CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE link link VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_events CHANGE description description VARCHAR(255) NOT NULL, CHANGE link link VARCHAR(255) NOT NULL');
    }
}
