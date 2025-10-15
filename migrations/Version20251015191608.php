<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251015191608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add note';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_lost_property ADD note VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_lost_property DROP note');
    }
}
