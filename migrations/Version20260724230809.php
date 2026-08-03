<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724230809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add hotel and event bidirectional relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_events CHANGE hotel_id hotel_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_events CHANGE hotel_id hotel_id INT DEFAULT NULL');
    }
}
