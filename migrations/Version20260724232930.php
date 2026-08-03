<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724232930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add date time to event';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_377ACBFC43625D9F ON aureum_events');
        $this->addSql('DROP INDEX IDX_377ACBFC8B8E8428 ON aureum_events');
        $this->addSql('ALTER TABLE aureum_events ADD event_date DATETIME NOT NULL, DROP created_at, DROP updated_at');
        $this->addSql('CREATE INDEX IDX_377ACBFCB5557BD1 ON aureum_events (event_date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_377ACBFCB5557BD1 ON aureum_events');
        $this->addSql('ALTER TABLE aureum_events ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, DROP event_date');
        $this->addSql('CREATE INDEX IDX_377ACBFC43625D9F ON aureum_events (updated_at)');
        $this->addSql('CREATE INDEX IDX_377ACBFC8B8E8428 ON aureum_events (created_at)');
    }
}
