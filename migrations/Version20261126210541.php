<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261126210541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add hotel location';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_hotels ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_hotels DROP latitude, DROP longitude');
    }
}
