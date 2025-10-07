<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251007223858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'remove fine date';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_fines DROP date');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_fines ADD date DATETIME NOT NULL');
    }
}
