<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251022161230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'change comment to note';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_fines CHANGE comment note VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_fines CHANGE note comment VARCHAR(255) DEFAULT NULL');
    }
}
