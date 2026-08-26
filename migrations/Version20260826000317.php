<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826000317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'turn amenity card items into a checklist';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE aureum_amenity_cards
            SET items = CASE
                WHEN TRIM(items) = '' THEN '[]'
                ELSE JSON_ARRAY(JSON_OBJECT('label', TRIM(items), 'done', status = 'completed'))
            END");
        $this->addSql('ALTER TABLE aureum_amenity_cards MODIFY items JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_amenity_cards MODIFY items LONGTEXT NOT NULL');
    }
}
