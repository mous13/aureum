<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725014422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add restaurant event relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_events ADD restaurant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE aureum_events ADD CONSTRAINT FK_377ACBFCB1E7706E FOREIGN KEY (restaurant_id) REFERENCES aureum_restaurants (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_377ACBFCB1E7706E ON aureum_events (restaurant_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_events DROP FOREIGN KEY FK_377ACBFCB1E7706E');
        $this->addSql('DROP INDEX IDX_377ACBFCB1E7706E ON aureum_events');
        $this->addSql('ALTER TABLE aureum_events DROP restaurant_id');
    }
}
