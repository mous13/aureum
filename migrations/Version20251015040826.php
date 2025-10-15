<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20251015040826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add pickup and dropoff';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_transfers ADD pickup VARCHAR(255) DEFAULT NULL, ADD dropoff VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_transfers DROP pickup, DROP dropoff');
    }
}
