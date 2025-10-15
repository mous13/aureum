<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20251015012919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_transfers ADD status VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_transfers DROP status');
    }
}
