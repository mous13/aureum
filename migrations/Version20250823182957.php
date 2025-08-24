<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250823182957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'adds hotels and employee';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_employees (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, hotel_id INT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_65A90A71A76ED395 (user_id), INDEX IDX_65A90A713243BB18 (hotel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE aureum_hotels (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, name VARCHAR(255) NOT NULL, join_date DATETIME NOT NULL, logo VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_80FF6A9377153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aureum_employees ADD CONSTRAINT FK_65A90A71A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_employees ADD CONSTRAINT FK_65A90A713243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_employees DROP FOREIGN KEY FK_65A90A71A76ED395');
        $this->addSql('ALTER TABLE aureum_employees DROP FOREIGN KEY FK_65A90A713243BB18');
        $this->addSql('DROP TABLE aureum_employees');
        $this->addSql('DROP TABLE aureum_hotels');
    }
}
