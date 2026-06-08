<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260608173943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add restaurants, cuisines, and tags';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_cuisines (name VARCHAR(255) NOT NULL, id INT AUTO_INCREMENT NOT NULL, hotel_id INT NOT NULL, INDEX IDX_8A58105B3243BB18 (hotel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_logs_restaurants (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, changes JSON DEFAULT NULL, notes LONGTEXT DEFAULT NULL, restaurant_id INT NOT NULL, performed_by_id INT NOT NULL, hotel_id INT NOT NULL, INDEX IDX_B675864BB1E7706E (restaurant_id), INDEX IDX_B675864B2E65C292 (performed_by_id), INDEX IDX_B675864B3243BB18 (hotel_id), INDEX IDX_B675864B3243BB188B8E8428 (hotel_id, created_at), INDEX IDX_B675864BB1E7706E8B8E8428 (restaurant_id, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_restaurants (name VARCHAR(255) NOT NULL, neighbourhood VARCHAR(255) NOT NULL, meal_periods JSON NOT NULL, dietary_requirements JSON NOT NULL, score INT DEFAULT 0 NOT NULL, id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, hotel_id INT NOT NULL, INDEX IDX_791F9AAC8B8E8428 (created_at), INDEX IDX_791F9AAC43625D9F (updated_at), INDEX IDX_791F9AAC3243BB18 (hotel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_restaurant_connections (restaurant_id INT NOT NULL, employee_id INT NOT NULL, INDEX IDX_4F455DD8B1E7706E (restaurant_id), INDEX IDX_4F455DD88C03F15C (employee_id), PRIMARY KEY (restaurant_id, employee_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_restaurant_cuisines (restaurant_id INT NOT NULL, cuisine_id INT NOT NULL, INDEX IDX_15A33A01B1E7706E (restaurant_id), INDEX IDX_15A33A01ED4BAC14 (cuisine_id), PRIMARY KEY (restaurant_id, cuisine_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_restaurant_tag (restaurant_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_3FA96C1DB1E7706E (restaurant_id), INDEX IDX_3FA96C1DBAD26311 (tag_id), PRIMARY KEY (restaurant_id, tag_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_tags (name VARCHAR(255) NOT NULL, id INT AUTO_INCREMENT NOT NULL, hotel_id INT NOT NULL, INDEX IDX_5262036C3243BB18 (hotel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aureum_votes (subject_type VARCHAR(50) NOT NULL, subject_id INT NOT NULL, type VARCHAR(10) NOT NULL, id INT AUTO_INCREMENT NOT NULL, employee_id INT NOT NULL, INDEX IDX_C7BF0CD68C03F15C (employee_id), UNIQUE INDEX unique_vote (employee_id, subject_type, subject_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aureum_cuisines ADD CONSTRAINT FK_8A58105B3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_logs_restaurants ADD CONSTRAINT FK_B675864BB1E7706E FOREIGN KEY (restaurant_id) REFERENCES aureum_restaurants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_logs_restaurants ADD CONSTRAINT FK_B675864B2E65C292 FOREIGN KEY (performed_by_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_logs_restaurants ADD CONSTRAINT FK_B675864B3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_restaurants ADD CONSTRAINT FK_791F9AAC3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_restaurant_connections ADD CONSTRAINT FK_4F455DD8B1E7706E FOREIGN KEY (restaurant_id) REFERENCES aureum_restaurants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_restaurant_connections ADD CONSTRAINT FK_4F455DD88C03F15C FOREIGN KEY (employee_id) REFERENCES aureum_employees (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_restaurant_cuisines ADD CONSTRAINT FK_15A33A01B1E7706E FOREIGN KEY (restaurant_id) REFERENCES aureum_restaurants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_restaurant_cuisines ADD CONSTRAINT FK_15A33A01ED4BAC14 FOREIGN KEY (cuisine_id) REFERENCES aureum_cuisines (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_restaurant_tag ADD CONSTRAINT FK_3FA96C1DB1E7706E FOREIGN KEY (restaurant_id) REFERENCES aureum_restaurants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_restaurant_tag ADD CONSTRAINT FK_3FA96C1DBAD26311 FOREIGN KEY (tag_id) REFERENCES aureum_tags (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_tags ADD CONSTRAINT FK_5262036C3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_votes ADD CONSTRAINT FK_C7BF0CD68C03F15C FOREIGN KEY (employee_id) REFERENCES aureum_employees (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_cuisines DROP FOREIGN KEY FK_8A58105B3243BB18');
        $this->addSql('ALTER TABLE aureum_logs_restaurants DROP FOREIGN KEY FK_B675864BB1E7706E');
        $this->addSql('ALTER TABLE aureum_logs_restaurants DROP FOREIGN KEY FK_B675864B2E65C292');
        $this->addSql('ALTER TABLE aureum_logs_restaurants DROP FOREIGN KEY FK_B675864B3243BB18');
        $this->addSql('ALTER TABLE aureum_restaurants DROP FOREIGN KEY FK_791F9AAC3243BB18');
        $this->addSql('ALTER TABLE aureum_restaurant_connections DROP FOREIGN KEY FK_4F455DD8B1E7706E');
        $this->addSql('ALTER TABLE aureum_restaurant_connections DROP FOREIGN KEY FK_4F455DD88C03F15C');
        $this->addSql('ALTER TABLE aureum_restaurant_cuisines DROP FOREIGN KEY FK_15A33A01B1E7706E');
        $this->addSql('ALTER TABLE aureum_restaurant_cuisines DROP FOREIGN KEY FK_15A33A01ED4BAC14');
        $this->addSql('ALTER TABLE aureum_restaurant_tag DROP FOREIGN KEY FK_3FA96C1DB1E7706E');
        $this->addSql('ALTER TABLE aureum_restaurant_tag DROP FOREIGN KEY FK_3FA96C1DBAD26311');
        $this->addSql('ALTER TABLE aureum_tags DROP FOREIGN KEY FK_5262036C3243BB18');
        $this->addSql('ALTER TABLE aureum_votes DROP FOREIGN KEY FK_C7BF0CD68C03F15C');
        $this->addSql('DROP TABLE aureum_cuisines');
        $this->addSql('DROP TABLE aureum_logs_restaurants');
        $this->addSql('DROP TABLE aureum_restaurants');
        $this->addSql('DROP TABLE aureum_restaurant_connections');
        $this->addSql('DROP TABLE aureum_restaurant_cuisines');
        $this->addSql('DROP TABLE aureum_restaurant_tag');
        $this->addSql('DROP TABLE aureum_tags');
        $this->addSql('DROP TABLE aureum_votes');
    }
}
