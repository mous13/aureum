<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add anonymisation marker to bookings and cascade booking log deletion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_bookings ADD anonymised_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_aureum_bookings_anonymised_at ON aureum_bookings (anonymised_at)');

        $this->addSql('ALTER TABLE aureum_logs_bookings DROP FOREIGN KEY FK_booking_log_booking');
        $this->addSql('DELETE FROM aureum_logs_bookings WHERE booking_id IS NULL');
        $this->addSql('ALTER TABLE aureum_logs_bookings CHANGE booking_id booking_id INT NOT NULL');
        $this->addSql('ALTER TABLE aureum_logs_bookings ADD CONSTRAINT FK_booking_log_booking
            FOREIGN KEY (booking_id) REFERENCES aureum_bookings (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_logs_bookings DROP FOREIGN KEY FK_booking_log_booking');
        $this->addSql('ALTER TABLE aureum_logs_bookings CHANGE booking_id booking_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE aureum_logs_bookings ADD CONSTRAINT FK_booking_log_booking
            FOREIGN KEY (booking_id) REFERENCES aureum_bookings (id)');

        $this->addSql('DROP INDEX IDX_aureum_bookings_anonymised_at ON aureum_bookings');
        $this->addSql('ALTER TABLE aureum_bookings DROP anonymised_at');
    }
}
