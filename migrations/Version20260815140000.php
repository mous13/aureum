<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add per-hotel per-module retention policies and anonymisation markers on guest records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_retention_policies (
            id INT AUTO_INCREMENT NOT NULL,
            hotel_id INT NOT NULL,
            module VARCHAR(50) NOT NULL,
            retain_for_months INT DEFAULT NULL,
            updated_by_id INT DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX IDX_5651E5FB3243BB18 (hotel_id),
            INDEX IDX_5651E5FB896DBBDE (updated_by_id),
            UNIQUE INDEX uniq_retention_hotel_module (hotel_id, module),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE aureum_retention_policies ADD CONSTRAINT FK_AR_POLICY_HOTEL
            FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_retention_policies ADD CONSTRAINT FK_AR_POLICY_UPDATED_BY
            FOREIGN KEY (updated_by_id) REFERENCES aureum_employees (id) ON DELETE SET NULL');

        foreach (['aureum_fines', 'aureum_packages', 'aureum_lost_property', 'aureum_transfers'] as $table) {
            $this->addSql("ALTER TABLE {$table} ADD anonymised_at DATETIME DEFAULT NULL");
            $this->addSql("CREATE INDEX IDX_{$table}_anonymised_at ON {$table} (anonymised_at)");
        }

        $this->addSql('ALTER TABLE aureum_logs_transfers DROP FOREIGN KEY FK_B669318C537048AF');
        $this->addSql('ALTER TABLE aureum_logs_transfers CHANGE transfer_id transfer_id INT NOT NULL');
        $this->addSql('ALTER TABLE aureum_logs_transfers ADD CONSTRAINT FK_B669318C537048AF
            FOREIGN KEY (transfer_id) REFERENCES aureum_transfers (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_logs_transfers DROP FOREIGN KEY FK_B669318C537048AF');
        $this->addSql('ALTER TABLE aureum_logs_transfers CHANGE transfer_id transfer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE aureum_logs_transfers ADD CONSTRAINT FK_B669318C537048AF
            FOREIGN KEY (transfer_id) REFERENCES aureum_transfers (id)');

        foreach (['aureum_fines', 'aureum_packages', 'aureum_lost_property', 'aureum_transfers'] as $table) {
            $this->addSql("DROP INDEX IDX_{$table}_anonymised_at ON {$table}");
            $this->addSql("ALTER TABLE {$table} DROP anonymised_at");
        }

        $this->addSql('DROP TABLE aureum_retention_policies');
    }
}
