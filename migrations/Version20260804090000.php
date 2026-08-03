<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add interconnecting and last let flags to rooms';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_rooms ADD interconnecting TINYINT NOT NULL, ADD last_let TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_rooms DROP interconnecting, DROP last_let');
    }
}
