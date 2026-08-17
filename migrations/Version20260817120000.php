<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'transfers become bookings: multi-type booking log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_transfers RENAME TO aureum_bookings');
        $this->addSql('ALTER TABLE aureum_bookings CHANGE driver vendor VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE aureum_bookings ADD type VARCHAR(255) DEFAULT NULL, ADD reference VARCHAR(255) DEFAULT NULL, ADD details JSON DEFAULT NULL');

        $this->addSql('UPDATE aureum_bookings SET type = \'private_transfer\', details = JSON_OBJECT()');
        $this->addSql('UPDATE aureum_bookings SET details = JSON_SET(details, \'$.pickup\', pickup) WHERE pickup IS NOT NULL AND pickup <> \'\'');
        $this->addSql('UPDATE aureum_bookings SET details = JSON_SET(details, \'$.dropoff\', dropoff) WHERE dropoff IS NOT NULL AND dropoff <> \'\'');

        $this->addSql('ALTER TABLE aureum_bookings MODIFY type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE aureum_bookings MODIFY details JSON NOT NULL');
        $this->addSql('ALTER TABLE aureum_bookings DROP pickup, DROP dropoff');

        $this->addSql('CREATE INDEX idx_booking_hotel_date ON aureum_bookings (hotel_id, date)');
        $this->addSql('CREATE INDEX idx_booking_hotel_type ON aureum_bookings (hotel_id, type)');

        $this->addSql('ALTER TABLE aureum_logs_transfers RENAME TO aureum_logs_bookings');
        $this->addSql('ALTER TABLE aureum_logs_bookings DROP FOREIGN KEY FK_B669318C537048AF');
        $this->addSql('DROP INDEX IDX_B669318C537048AF ON aureum_logs_bookings');
        $this->addSql('DROP INDEX IDX_B669318C537048AF8B8E8428 ON aureum_logs_bookings');
        $this->addSql('DROP INDEX IDX_B669318C3243BB188B8E8428 ON aureum_logs_bookings');
        $this->addSql('ALTER TABLE aureum_logs_bookings CHANGE transfer_id booking_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_booking_log_booking ON aureum_logs_bookings (booking_id)');
        $this->addSql('CREATE INDEX idx_booking_log_booking_created ON aureum_logs_bookings (booking_id, created_at)');
        $this->addSql('CREATE INDEX idx_booking_log_hotel_created ON aureum_logs_bookings (hotel_id, created_at)');
        $this->addSql('ALTER TABLE aureum_logs_bookings ADD CONSTRAINT FK_booking_log_booking FOREIGN KEY (booking_id) REFERENCES aureum_bookings (id)');

        $this->addSql("UPDATE aureum_hotels SET enabled_modules = REPLACE(CAST(enabled_modules AS CHAR), '\"transfers\"', '\"bookings\"')");
        $this->addSql("UPDATE aureum_hotel_roles SET permissions = REPLACE(REPLACE(CAST(permissions AS CHAR), '\"transfers.view\"', '\"bookings.view\"'), '\"transfers.manage\"', '\"bookings.manage\"')");
        $this->addSql("UPDATE aureum_announcements SET module = 'bookings' WHERE module = 'transfers'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE aureum_announcements SET module = 'transfers' WHERE module = 'bookings'");
        $this->addSql("UPDATE aureum_hotel_roles SET permissions = REPLACE(REPLACE(CAST(permissions AS CHAR), '\"bookings.view\"', '\"transfers.view\"'), '\"bookings.manage\"', '\"transfers.manage\"')");
        $this->addSql("UPDATE aureum_hotels SET enabled_modules = REPLACE(CAST(enabled_modules AS CHAR), '\"bookings\"', '\"transfers\"')");

        $this->addSql('ALTER TABLE aureum_logs_bookings DROP FOREIGN KEY FK_booking_log_booking');
        $this->addSql('DROP INDEX idx_booking_log_booking ON aureum_logs_bookings');
        $this->addSql('DROP INDEX idx_booking_log_booking_created ON aureum_logs_bookings');
        $this->addSql('DROP INDEX idx_booking_log_hotel_created ON aureum_logs_bookings');
        $this->addSql('ALTER TABLE aureum_logs_bookings CHANGE booking_id transfer_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_B669318C537048AF ON aureum_logs_bookings (transfer_id)');
        $this->addSql('CREATE INDEX IDX_B669318C537048AF8B8E8428 ON aureum_logs_bookings (transfer_id, created_at)');
        $this->addSql('CREATE INDEX IDX_B669318C3243BB188B8E8428 ON aureum_logs_bookings (hotel_id, created_at)');
        $this->addSql('ALTER TABLE aureum_logs_bookings RENAME TO aureum_logs_transfers');

        $this->addSql('DROP INDEX idx_booking_hotel_date ON aureum_bookings');
        $this->addSql('DROP INDEX idx_booking_hotel_type ON aureum_bookings');
        $this->addSql('ALTER TABLE aureum_bookings ADD pickup VARCHAR(255) DEFAULT NULL, ADD dropoff VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE aureum_bookings SET pickup = JSON_UNQUOTE(JSON_EXTRACT(details, \'$.pickup\')), dropoff = JSON_UNQUOTE(JSON_EXTRACT(details, \'$.dropoff\'))');
        $this->addSql('ALTER TABLE aureum_bookings DROP type, DROP reference, DROP details');
        $this->addSql('ALTER TABLE aureum_bookings CHANGE vendor driver VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE aureum_bookings RENAME TO aureum_transfers');
        $this->addSql('ALTER TABLE aureum_logs_transfers ADD CONSTRAINT FK_B669318C537048AF FOREIGN KEY (transfer_id) REFERENCES aureum_transfers (id)');
    }
}
