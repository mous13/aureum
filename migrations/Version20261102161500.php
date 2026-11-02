<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261102161500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add bars directory';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_bar_styles (name VARCHAR(255) NOT NULL, id INT AUTO_INCREMENT NOT NULL, hotel_id INT NOT NULL, INDEX IDX_662D175A3243BB18 (hotel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_bars (name VARCHAR(255) NOT NULL, neighbourhood VARCHAR(255) NOT NULL, street VARCHAR(255) NOT NULL, dress_code VARCHAR(50) DEFAULT NULL, entry_policy VARCHAR(50) DEFAULT NULL, happy_hour VARCHAR(255) DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, opening_times JSON DEFAULT NULL, google_place_id VARCHAR(255) DEFAULT NULL, opening_times_synced_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, hotel_id INT NOT NULL, INDEX IDX_10A5ED3B8B8E8428 (created_at), INDEX IDX_10A5ED3B43625D9F (updated_at), INDEX IDX_10A5ED3B3243BB18 (hotel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_bar_connections (bar_id INT NOT NULL, employee_id INT NOT NULL, INDEX IDX_B912D67D89A253A (bar_id), INDEX IDX_B912D67D8C03F15C (employee_id), PRIMARY KEY (bar_id, employee_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_bar_bar_styles (bar_id INT NOT NULL, bar_style_id INT NOT NULL, INDEX IDX_136F0A7C89A253A (bar_id), INDEX IDX_136F0A7CAE5E191F (bar_style_id), PRIMARY KEY (bar_id, bar_style_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_bar_tags (bar_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_235A32B889A253A (bar_id), INDEX IDX_235A32B8BAD26311 (tag_id), PRIMARY KEY (bar_id, tag_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_logs_bars (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, changes JSON DEFAULT NULL, notes LONGTEXT DEFAULT NULL, bar_id INT NOT NULL, performed_by_id INT NOT NULL, hotel_id INT NOT NULL, INDEX IDX_FF65ED0B89A253A (bar_id), INDEX IDX_FF65ED0B2E65C292 (performed_by_id), INDEX IDX_FF65ED0B3243BB18 (hotel_id), INDEX IDX_FF65ED0B3243BB188B8E8428 (hotel_id, created_at), INDEX IDX_FF65ED0B89A253A8B8E8428 (bar_id, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aureum_events ADD bar_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_377ACBFC89A253A ON aureum_events (bar_id)');

        $this->addSql('ALTER TABLE aureum_bar_styles ADD CONSTRAINT FK_662D175A3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_bars ADD CONSTRAINT FK_10A5ED3B3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_bar_connections ADD CONSTRAINT FK_B912D67D89A253A FOREIGN KEY (bar_id) REFERENCES aureum_bars (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_bar_connections ADD CONSTRAINT FK_B912D67D8C03F15C FOREIGN KEY (employee_id) REFERENCES aureum_employees (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_bar_bar_styles ADD CONSTRAINT FK_136F0A7C89A253A FOREIGN KEY (bar_id) REFERENCES aureum_bars (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_bar_bar_styles ADD CONSTRAINT FK_136F0A7CAE5E191F FOREIGN KEY (bar_style_id) REFERENCES aureum_bar_styles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_bar_tags ADD CONSTRAINT FK_235A32B889A253A FOREIGN KEY (bar_id) REFERENCES aureum_bars (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_bar_tags ADD CONSTRAINT FK_235A32B8BAD26311 FOREIGN KEY (tag_id) REFERENCES aureum_tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_logs_bars ADD CONSTRAINT FK_FF65ED0B89A253A FOREIGN KEY (bar_id) REFERENCES aureum_bars (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_logs_bars ADD CONSTRAINT FK_FF65ED0B2E65C292 FOREIGN KEY (performed_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_logs_bars ADD CONSTRAINT FK_FF65ED0B3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_events ADD CONSTRAINT FK_377ACBFC89A253A FOREIGN KEY (bar_id) REFERENCES aureum_bars (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_events DROP FOREIGN KEY FK_377ACBFC89A253A');
        $this->addSql('ALTER TABLE aureum_logs_bars DROP FOREIGN KEY FK_FF65ED0B89A253A');
        $this->addSql('ALTER TABLE aureum_logs_bars DROP FOREIGN KEY FK_FF65ED0B2E65C292');
        $this->addSql('ALTER TABLE aureum_logs_bars DROP FOREIGN KEY FK_FF65ED0B3243BB18');
        $this->addSql('ALTER TABLE aureum_bar_tags DROP FOREIGN KEY FK_235A32B889A253A');
        $this->addSql('ALTER TABLE aureum_bar_tags DROP FOREIGN KEY FK_235A32B8BAD26311');
        $this->addSql('ALTER TABLE aureum_bar_bar_styles DROP FOREIGN KEY FK_136F0A7C89A253A');
        $this->addSql('ALTER TABLE aureum_bar_bar_styles DROP FOREIGN KEY FK_136F0A7CAE5E191F');
        $this->addSql('ALTER TABLE aureum_bar_connections DROP FOREIGN KEY FK_B912D67D89A253A');
        $this->addSql('ALTER TABLE aureum_bar_connections DROP FOREIGN KEY FK_B912D67D8C03F15C');
        $this->addSql('ALTER TABLE aureum_bars DROP FOREIGN KEY FK_10A5ED3B3243BB18');
        $this->addSql('ALTER TABLE aureum_bar_styles DROP FOREIGN KEY FK_662D175A3243BB18');
        $this->addSql('DROP INDEX IDX_377ACBFC89A253A ON aureum_events');
        $this->addSql('ALTER TABLE aureum_events DROP bar_id');
        $this->addSql('DROP TABLE aureum_logs_bars');
        $this->addSql('DROP TABLE aureum_bar_tags');
        $this->addSql('DROP TABLE aureum_bar_bar_styles');
        $this->addSql('DROP TABLE aureum_bar_connections');
        $this->addSql('DROP TABLE aureum_bars');
        $this->addSql('DROP TABLE aureum_bar_styles');
    }
}
