<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608174334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add street to restaurants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_restaurants ADD street VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_restaurants DROP street');
    }
}
