<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add daily amenity boards and their cards';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_amenity_boards (
            id INT AUTO_INCREMENT NOT NULL,
            hotel_id INT NOT NULL,
            created_by_id INT NOT NULL,
            date DATE NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_62B9518A3243BB18 (hotel_id),
            INDEX IDX_62B9518AB03A8386 (created_by_id),
            UNIQUE INDEX uniq_amenity_board_hotel_date (hotel_id, date),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE aureum_amenity_cards (
            id INT AUTO_INCREMENT NOT NULL,
            board_id INT NOT NULL,
            hotel_id INT NOT NULL,
            room_number VARCHAR(20) NOT NULL,
            guest_last_name VARCHAR(100) DEFAULT NULL,
            items LONGTEXT NOT NULL,
            status VARCHAR(50) NOT NULL,
            position INT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            anonymised_at DATETIME DEFAULT NULL,
            INDEX IDX_37B256B3E7EC5785 (board_id),
            INDEX IDX_37B256B33243BB18 (hotel_id),
            INDEX idx_amenity_card_board_status (board_id, status),
            INDEX IDX_aureum_amenity_cards_anonymised_at (anonymised_at),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE aureum_amenity_boards ADD CONSTRAINT FK_62B9518A3243BB18
            FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_amenity_boards ADD CONSTRAINT FK_62B9518AB03A8386
            FOREIGN KEY (created_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_amenity_cards ADD CONSTRAINT FK_37B256B3E7EC5785
            FOREIGN KEY (board_id) REFERENCES aureum_amenity_boards (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_amenity_cards ADD CONSTRAINT FK_37B256B33243BB18
            FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE aureum_amenity_cards');
        $this->addSql('DROP TABLE aureum_amenity_boards');
    }
}
