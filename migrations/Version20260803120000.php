<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add per-type detail fields to rooms and floor features';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_rooms ADD comments LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE aureum_floor_features ADD department VARCHAR(100) DEFAULT NULL, ADD contents LONGTEXT DEFAULT NULL, ADD comments LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_rooms DROP comments');
        $this->addSql('ALTER TABLE aureum_floor_features DROP department, DROP contents, DROP comments');
    }
}
