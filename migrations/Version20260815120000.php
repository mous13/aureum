<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add inventory catalogue, stock counts and stock movements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_inventories (
            id INT AUTO_INCREMENT NOT NULL,
            hotel_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            position INT NOT NULL,
            active TINYINT(1) NOT NULL,
            INDEX IDX_47F06BB53243BB18 (hotel_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE aureum_storage_locations (
            id INT AUTO_INCREMENT NOT NULL,
            hotel_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(32) NOT NULL,
            position INT NOT NULL,
            active TINYINT(1) NOT NULL,
            INDEX IDX_A04C039E3243BB18 (hotel_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE aureum_inventory_categories (
            id INT AUTO_INCREMENT NOT NULL,
            inventory_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            position INT NOT NULL,
            INDEX IDX_1D82C37D9EEA759 (inventory_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE aureum_inventory_items (
            id INT AUTO_INCREMENT NOT NULL,
            category_id INT NOT NULL,
            location_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            unit VARCHAR(50) NOT NULL,
            pack_size INT DEFAULT NULL,
            pack_label VARCHAR(50) DEFAULT NULL,
            lead_time_days INT DEFAULT NULL,
            safety_buffer_days INT NOT NULL,
            active TINYINT(1) NOT NULL,
            INDEX IDX_E81F4CB112469DE2 (category_id),
            INDEX IDX_E81F4CB164D218E (location_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE aureum_stock_counts (
            id INT AUTO_INCREMENT NOT NULL,
            inventory_id INT NOT NULL,
            counted_by_id INT NOT NULL,
            hotel_id INT NOT NULL,
            counted_at DATETIME NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            INDEX IDX_91A653529EEA759 (inventory_id),
            INDEX IDX_91A653527D70F0D2 (counted_by_id),
            INDEX IDX_91A653523243BB18 (hotel_id),
            INDEX IDX_91A653523243BB185CF260CC (hotel_id, counted_at),
            INDEX IDX_91A653529EEA7595CF260CC (inventory_id, counted_at),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE aureum_stock_count_lines (
            id INT AUTO_INCREMENT NOT NULL,
            stock_count_id INT NOT NULL,
            item_id INT NOT NULL,
            location_id INT DEFAULT NULL,
            quantity INT NOT NULL,
            INDEX IDX_11ABEFB59CE6EC8 (stock_count_id),
            INDEX IDX_11ABEFB5126F525E (item_id),
            INDEX IDX_11ABEFB564D218E (location_id),
            UNIQUE INDEX uniq_count_item (stock_count_id, item_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE aureum_stock_movements (
            id INT AUTO_INCREMENT NOT NULL,
            item_id INT NOT NULL,
            recorded_by_id INT NOT NULL,
            hotel_id INT NOT NULL,
            destination_id INT DEFAULT NULL,
            direction VARCHAR(16) NOT NULL,
            reason VARCHAR(32) NOT NULL,
            quantity INT NOT NULL,
            occurred_at DATETIME NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            INDEX IDX_75239D35126F525E (item_id),
            INDEX IDX_75239D35D05A957B (recorded_by_id),
            INDEX IDX_75239D353243BB18 (hotel_id),
            INDEX IDX_75239D35816C6140 (destination_id),
            INDEX IDX_75239D35126F525E87C03D1B (item_id, occurred_at),
            INDEX IDX_75239D353243BB1887C03D1B (hotel_id, occurred_at),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE aureum_logs_inventory_items (
            id INT AUTO_INCREMENT NOT NULL,
            item_id INT NOT NULL,
            performed_by_id INT NOT NULL,
            hotel_id INT NOT NULL,
            action VARCHAR(255) NOT NULL,
            changes JSON DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_22423328126F525E (item_id),
            INDEX IDX_224233282E65C292 (performed_by_id),
            INDEX IDX_224233283243BB18 (hotel_id),
            INDEX IDX_224233283243BB188B8E8428 (hotel_id, created_at),
            INDEX IDX_22423328126F525E8B8E8428 (item_id, created_at),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE aureum_inventories ADD CONSTRAINT FK_inventories_hotel FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_storage_locations ADD CONSTRAINT FK_locations_hotel FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_inventory_categories ADD CONSTRAINT FK_categories_inventory FOREIGN KEY (inventory_id) REFERENCES aureum_inventories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_inventory_items ADD CONSTRAINT FK_items_category FOREIGN KEY (category_id) REFERENCES aureum_inventory_categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_inventory_items ADD CONSTRAINT FK_items_location FOREIGN KEY (location_id) REFERENCES aureum_storage_locations (id)');
        $this->addSql('ALTER TABLE aureum_stock_counts ADD CONSTRAINT FK_counts_inventory FOREIGN KEY (inventory_id) REFERENCES aureum_inventories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_stock_counts ADD CONSTRAINT FK_counts_employee FOREIGN KEY (counted_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_stock_counts ADD CONSTRAINT FK_counts_hotel FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_stock_count_lines ADD CONSTRAINT FK_lines_count FOREIGN KEY (stock_count_id) REFERENCES aureum_stock_counts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_stock_count_lines ADD CONSTRAINT FK_lines_item FOREIGN KEY (item_id) REFERENCES aureum_inventory_items (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_stock_count_lines ADD CONSTRAINT FK_lines_location FOREIGN KEY (location_id) REFERENCES aureum_storage_locations (id)');
        $this->addSql('ALTER TABLE aureum_stock_movements ADD CONSTRAINT FK_movements_item FOREIGN KEY (item_id) REFERENCES aureum_inventory_items (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_stock_movements ADD CONSTRAINT FK_movements_employee FOREIGN KEY (recorded_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_stock_movements ADD CONSTRAINT FK_movements_hotel FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_stock_movements ADD CONSTRAINT FK_movements_destination FOREIGN KEY (destination_id) REFERENCES aureum_storage_locations (id)');
        $this->addSql('ALTER TABLE aureum_logs_inventory_items ADD CONSTRAINT FK_item_logs_item FOREIGN KEY (item_id) REFERENCES aureum_inventory_items (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_logs_inventory_items ADD CONSTRAINT FK_item_logs_employee FOREIGN KEY (performed_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_logs_inventory_items ADD CONSTRAINT FK_item_logs_hotel FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE aureum_logs_inventory_items');
        $this->addSql('DROP TABLE aureum_stock_movements');
        $this->addSql('DROP TABLE aureum_stock_count_lines');
        $this->addSql('DROP TABLE aureum_stock_counts');
        $this->addSql('DROP TABLE aureum_inventory_items');
        $this->addSql('DROP TABLE aureum_inventory_categories');
        $this->addSql('DROP TABLE aureum_storage_locations');
        $this->addSql('DROP TABLE aureum_inventories');
    }
}
