<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811120000 extends AbstractMigration
{
    private const LEGACY_AUTHOR_ID = 1;

    public function getDescription(): string
    {
        return 'replace the single comments field on rooms and floor features with employee comment threads';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_room_comments (
            id INT AUTO_INCREMENT NOT NULL,
            room_id INT NOT NULL,
            author_id INT NOT NULL,
            body LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            edited_at DATETIME DEFAULT NULL,
            INDEX IDX_D2990EF454177093 (room_id),
            INDEX IDX_D2990EF4F675F31B (author_id),
            INDEX IDX_D2990EF4541770938B8E8428 (room_id, created_at),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aureum_room_comments ADD CONSTRAINT FK_D2990EF454177093 FOREIGN KEY (room_id) REFERENCES aureum_rooms (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_room_comments ADD CONSTRAINT FK_D2990EF4F675F31B FOREIGN KEY (author_id) REFERENCES aureum_employees (id)');

        $this->addSql('CREATE TABLE aureum_floor_feature_comments (
            id INT AUTO_INCREMENT NOT NULL,
            feature_id INT NOT NULL,
            author_id INT NOT NULL,
            body LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            edited_at DATETIME DEFAULT NULL,
            INDEX IDX_9C8A6A4E60E4B879 (feature_id),
            INDEX IDX_9C8A6A4EF675F31B (author_id),
            INDEX IDX_9C8A6A4E60E4B8798B8E8428 (feature_id, created_at),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aureum_floor_feature_comments ADD CONSTRAINT FK_9C8A6A4E60E4B879 FOREIGN KEY (feature_id) REFERENCES aureum_floor_features (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_floor_feature_comments ADD CONSTRAINT FK_9C8A6A4EF675F31B FOREIGN KEY (author_id) REFERENCES aureum_employees (id)');

        $this->addSql('INSERT INTO aureum_room_comments (room_id, author_id, body, created_at)
            SELECT r.id, e.id, r.comments, NOW()
            FROM aureum_rooms r
            JOIN aureum_employees e ON e.id = :author
            WHERE r.comments IS NOT NULL AND TRIM(r.comments) <> \'\'', [
            'author' => self::LEGACY_AUTHOR_ID,
        ]);

        $this->addSql('INSERT INTO aureum_floor_feature_comments (feature_id, author_id, body, created_at)
            SELECT f.id, e.id, f.comments, NOW()
            FROM aureum_floor_features f
            JOIN aureum_employees e ON e.id = :author
            WHERE f.comments IS NOT NULL AND TRIM(f.comments) <> \'\'', [
            'author' => self::LEGACY_AUTHOR_ID,
        ]);

        $this->addSql('ALTER TABLE aureum_rooms DROP comments');
        $this->addSql('ALTER TABLE aureum_floor_features DROP comments');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_rooms ADD comments LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE aureum_floor_features ADD comments LONGTEXT DEFAULT NULL');

        $this->addSql('UPDATE aureum_rooms r SET r.comments = (
            SELECT GROUP_CONCAT(c.body ORDER BY c.created_at SEPARATOR \'\n\n\')
            FROM aureum_room_comments c
            WHERE c.room_id = r.id
        )');
        $this->addSql('UPDATE aureum_floor_features f SET f.comments = (
            SELECT GROUP_CONCAT(c.body ORDER BY c.created_at SEPARATOR \'\n\n\')
            FROM aureum_floor_feature_comments c
            WHERE c.feature_id = f.id
        )');

        $this->addSql('DROP TABLE aureum_room_comments');
        $this->addSql('DROP TABLE aureum_floor_feature_comments');
    }
}
