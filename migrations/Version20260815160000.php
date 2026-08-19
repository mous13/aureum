<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'require employees created with a generated password to choose their own at first sign-in';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_employees ADD must_change_password TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_employees DROP must_change_password');
    }
}
