<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20251015004259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add comment nullable and transfers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_transfers (id INT AUTO_INCREMENT NOT NULL, middleman_id INT DEFAULT NULL, hotel_id INT NOT NULL, guest VARCHAR(255) DEFAULT NULL, number VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, driver VARCHAR(255) DEFAULT NULL, cost VARCHAR(255) DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, INDEX IDX_5F01F06933397F4B (middleman_id), INDEX IDX_5F01F0693243BB18 (hotel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aureum_transfers ADD CONSTRAINT FK_5F01F06933397F4B FOREIGN KEY (middleman_id) REFERENCES aureum_employees (id)');
        $this->addSql('ALTER TABLE aureum_transfers ADD CONSTRAINT FK_5F01F0693243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id)');
        $this->addSql('ALTER TABLE aureum_fines CHANGE comment comment VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_transfers DROP FOREIGN KEY FK_5F01F06933397F4B');
        $this->addSql('ALTER TABLE aureum_transfers DROP FOREIGN KEY FK_5F01F0693243BB18');
        $this->addSql('DROP TABLE aureum_transfers');
        $this->addSql('ALTER TABLE aureum_fines CHANGE comment comment VARCHAR(255) NOT NULL');
    }
}
