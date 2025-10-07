<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251007170534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'adds relation between package and hotel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_packages ADD hotel_id INT NOT NULL');
        $this->addSql('ALTER TABLE aureum_packages ADD CONSTRAINT FK_D093720B3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('CREATE INDEX IDX_D093720B3243BB18 ON aureum_packages (hotel_id)');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_packages DROP FOREIGN KEY FK_D093720B3243BB18');
        $this->addSql('DROP INDEX IDX_D093720B3243BB18 ON aureum_packages');
        $this->addSql('ALTER TABLE aureum_packages DROP hotel_id');
    }
}
