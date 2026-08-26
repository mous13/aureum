<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826202147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add comments on amenity cards';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_amenity_card_comments (
            id INT AUTO_INCREMENT NOT NULL,
            card_id INT NOT NULL,
            author_id INT NOT NULL,
            body LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            edited_at DATETIME DEFAULT NULL,
            INDEX IDX_67243CBC4ACC9A20 (card_id),
            INDEX IDX_67243CBCF675F31B (author_id),
            INDEX idx_amenity_card_comment (card_id, created_at),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE aureum_amenity_card_comments ADD CONSTRAINT FK_67243CBC4ACC9A20
            FOREIGN KEY (card_id) REFERENCES aureum_amenity_cards (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_amenity_card_comments ADD CONSTRAINT FK_67243CBCF675F31B
            FOREIGN KEY (author_id) REFERENCES aureum_employees (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE aureum_amenity_card_comments');
    }
}
