<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901131556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'remove restaurant rating system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_votes DROP FOREIGN KEY `FK_C7BF0CD68C03F15C`');
        $this->addSql('DROP TABLE aureum_votes');
        $this->addSql('ALTER TABLE aureum_restaurants DROP score');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_votes (
            subject_type VARCHAR(50) NOT NULL,
            subject_id INT NOT NULL,
            type VARCHAR(10) NOT NULL,
            id INT AUTO_INCREMENT NOT NULL,
            employee_id INT NOT NULL,
            INDEX IDX_C7BF0CD68C03F15C (employee_id),
            UNIQUE INDEX unique_vote (employee_id, subject_type, subject_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aureum_votes ADD CONSTRAINT FK_C7BF0CD68C03F15C FOREIGN KEY (employee_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_restaurants ADD score INT DEFAULT 0 NOT NULL');
    }
}
