<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803150000 extends AbstractMigration
{
    private const DEFAULT_TYPES = [
        'TVUN' => 'Two Double Premium',
        'OVUN' => 'One Double Premium',
        'OSTN' => 'One Double Standard',
        'TSTN' => 'Two Double Standard',
    ];

    public function getDescription(): string
    {
        return 'replace the room type enum with per-hotel room type entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_room_types (
            id INT AUTO_INCREMENT NOT NULL,
            hotel_id INT NOT NULL,
            code VARCHAR(20) NOT NULL,
            name VARCHAR(255) NOT NULL,
            archived TINYINT NOT NULL,
            INDEX IDX_345142F83243BB18 (hotel_id),
            UNIQUE INDEX uniq_hotel_code (hotel_id, code),
            UNIQUE INDEX uniq_hotel_name (hotel_id, name),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aureum_room_types ADD CONSTRAINT FK_345142F83243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id) ON DELETE CASCADE');

        foreach (self::DEFAULT_TYPES as $code => $name) {
            $this->addSql(
                'INSERT IGNORE INTO aureum_room_types (hotel_id, code, name, archived)
                 SELECT h.id, :code, :name, 0 FROM aureum_hotels h',
                ['code' => $code, 'name' => $name],
            );
        }

        $this->addSql("INSERT IGNORE INTO aureum_room_types (hotel_id, code, name, archived)
            SELECT DISTINCT f.hotel_id, UPPER(LEFT(REPLACE(r.room_type, ' ', ''), 20)), r.room_type, 0
            FROM aureum_rooms r
            JOIN aureum_floors f ON f.id = r.floor_id");

        $this->addSql('ALTER TABLE aureum_rooms ADD room_type_id INT DEFAULT NULL');
        $this->addSql('UPDATE aureum_rooms r
            JOIN aureum_floors f ON f.id = r.floor_id
            JOIN aureum_room_types t ON t.hotel_id = f.hotel_id AND t.name = r.room_type
            SET r.room_type_id = t.id');

        $this->addSql('ALTER TABLE aureum_rooms MODIFY room_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE aureum_rooms ADD CONSTRAINT FK_EA956C8F296E3073 FOREIGN KEY (room_type_id) REFERENCES aureum_room_types (id)');
        $this->addSql('CREATE INDEX IDX_EA956C8F296E3073 ON aureum_rooms (room_type_id)');
        $this->addSql('ALTER TABLE aureum_rooms DROP room_type');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_rooms ADD room_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('UPDATE aureum_rooms r
            JOIN aureum_room_types t ON t.id = r.room_type_id
            SET r.room_type = t.name');
        $this->addSql('ALTER TABLE aureum_rooms MODIFY room_type VARCHAR(50) NOT NULL');

        $this->addSql('ALTER TABLE aureum_rooms DROP FOREIGN KEY FK_EA956C8F296E3073');
        $this->addSql('DROP INDEX IDX_EA956C8F296E3073 ON aureum_rooms');
        $this->addSql('ALTER TABLE aureum_rooms DROP room_type_id');

        $this->addSql('ALTER TABLE aureum_room_types DROP FOREIGN KEY FK_345142F83243BB18');
        $this->addSql('DROP TABLE aureum_room_types');
    }
}
