<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731175039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'change eventdate to start and end';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_events ADD end DATETIME DEFAULT NULL, CHANGE event_date start DATETIME NOT NULL');
        $this->addSql('CREATE INDEX IDX_377ACBFC9F79558F ON aureum_events (start)');
        $this->addSql('CREATE INDEX IDX_377ACBFCFC33B1 ON aureum_events (end)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_377ACBFC9F79558F ON aureum_events');
        $this->addSql('DROP INDEX IDX_377ACBFCFC33B1 ON aureum_events');
        $this->addSql('ALTER TABLE aureum_events DROP end, CHANGE start event_date DATETIME NOT NULL');
    }
}
