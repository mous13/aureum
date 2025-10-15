<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20251015034415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add date';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_transfers ADD date DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_transfers DROP date');
    }
}
