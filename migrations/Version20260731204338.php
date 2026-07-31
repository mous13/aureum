<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731204338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'make event description text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_events CHANGE description description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_events CHANGE description description VARCHAR(255) DEFAULT NULL');
    }
}
