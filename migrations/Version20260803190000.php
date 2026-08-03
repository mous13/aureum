<?php

declare(strict_types=1);

namespace AureumDoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803190000 extends AbstractMigration
{
    private const MODULES = ['packages', 'transfers', 'lost_property', 'fines', 'restaurants', 'rooms', 'events'];

    public function getDescription(): string
    {
        return 'per-hotel RBAC: hotel roles, role assignments, module enablement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE aureum_hotel_roles (
            id INT AUTO_INCREMENT NOT NULL,
            hotel_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            permissions JSON NOT NULL,
            INDEX IDX_81760D8B3243BB18 (hotel_id),
            UNIQUE INDEX uniq_hotel_role_name (hotel_id, name),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aureum_hotel_roles ADD CONSTRAINT FK_81760D8B3243BB18 FOREIGN KEY (hotel_id) REFERENCES aureum_hotels (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE aureum_hotel_role_employees (
            hotel_role_id INT NOT NULL,
            employee_id INT NOT NULL,
            INDEX IDX_CAB289715493C85 (hotel_role_id),
            INDEX IDX_CAB289718C03F15C (employee_id),
            PRIMARY KEY (hotel_role_id, employee_id)
        ) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aureum_hotel_role_employees ADD CONSTRAINT FK_CAB289715493C85 FOREIGN KEY (hotel_role_id) REFERENCES aureum_hotel_roles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aureum_hotel_role_employees ADD CONSTRAINT FK_CAB289718C03F15C FOREIGN KEY (employee_id) REFERENCES aureum_employees (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE aureum_hotels ADD enabled_modules JSON NOT NULL');

        $this->addSql('UPDATE aureum_hotels SET enabled_modules = :modules', [
            'modules' => json_encode(self::MODULES),
        ]);

        $allPermissions = [];
        foreach (self::MODULES as $module) {
            $allPermissions[] = "{$module}.view";
            $allPermissions[] = "{$module}.manage";
        }

        $this->addSql('INSERT INTO aureum_hotel_roles (hotel_id, name, permissions)
            SELECT h.id, :name, :permissions FROM aureum_hotels h', [
            'name' => 'Staff',
            'permissions' => json_encode($allPermissions),
        ]);

        $this->addSql("INSERT INTO aureum_hotel_role_employees (hotel_role_id, employee_id)
            SELECT r.id, e.id
            FROM aureum_employees e
            JOIN aureum_hotel_roles r ON r.hotel_id = e.hotel_id AND r.name = 'Staff'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aureum_hotels DROP enabled_modules');
        $this->addSql('DROP TABLE aureum_hotel_role_employees');
        $this->addSql('DROP TABLE aureum_hotel_roles');
    }
}
