<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'rename booking indexes left behind by the transfers rename';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_bookings RENAME INDEX idx_5f01f06933397f4b TO IDX_31A38E9933397F4B');
        $this->addSql('ALTER TABLE aureum_bookings RENAME INDEX idx_5f01f0693243bb18 TO IDX_31A38E993243BB18');
        $this->addSql('ALTER TABLE aureum_logs_bookings RENAME INDEX idx_booking_log_booking TO IDX_3AC75E9F3301C60');
        $this->addSql('ALTER TABLE aureum_logs_bookings RENAME INDEX idx_b669318c2e65c292 TO IDX_3AC75E9F2E65C292');
        $this->addSql('ALTER TABLE aureum_logs_bookings RENAME INDEX idx_b669318c3243bb18 TO IDX_3AC75E9F3243BB18');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_bookings RENAME INDEX IDX_31A38E9933397F4B TO idx_5f01f06933397f4b');
        $this->addSql('ALTER TABLE aureum_bookings RENAME INDEX IDX_31A38E993243BB18 TO idx_5f01f0693243bb18');
        $this->addSql('ALTER TABLE aureum_logs_bookings RENAME INDEX IDX_3AC75E9F3301C60 TO idx_booking_log_booking');
        $this->addSql('ALTER TABLE aureum_logs_bookings RENAME INDEX IDX_3AC75E9F2E65C292 TO idx_b669318c2e65c292');
        $this->addSql('ALTER TABLE aureum_logs_bookings RENAME INDEX IDX_3AC75E9F3243BB18 TO idx_b669318c3243bb18');
    }
}
