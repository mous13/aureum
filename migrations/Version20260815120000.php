<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'allow employees to be archived so offboarding keeps the audit trail intact';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_employees DROP FOREIGN KEY FK_65A90A71A76ED395');
        $this->addSql('ALTER TABLE aureum_employees CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE aureum_employees ADD CONSTRAINT FK_65A90A71A76ED395
             FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL'
        );

        $this->addSql('ALTER TABLE aureum_employees ADD archived_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_65A90A71_archived_at ON aureum_employees (archived_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM aureum_employees WHERE user_id IS NULL');

        $this->addSql('DROP INDEX IDX_65A90A71_archived_at ON aureum_employees');
        $this->addSql('ALTER TABLE aureum_employees DROP archived_at');

        $this->addSql('ALTER TABLE aureum_employees DROP FOREIGN KEY FK_65A90A71A76ED395');
        $this->addSql('ALTER TABLE aureum_employees CHANGE user_id user_id INT NOT NULL');
        $this->addSql(
            'ALTER TABLE aureum_employees ADD CONSTRAINT FK_65A90A71A76ED395
             FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
        );
    }
}
