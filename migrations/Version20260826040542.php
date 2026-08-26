<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826040542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'make sop sign-offs append-only so every confirmation is kept';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_sop_sign_offs
            DROP INDEX uniq_sop_sign_off,
            ADD INDEX idx_sop_sign_off_lookup (sop_id, employee_id, version)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_sop_sign_offs
            DROP INDEX idx_sop_sign_off_lookup,
            ADD UNIQUE INDEX uniq_sop_sign_off (sop_id, employee_id, version)');
    }
}
