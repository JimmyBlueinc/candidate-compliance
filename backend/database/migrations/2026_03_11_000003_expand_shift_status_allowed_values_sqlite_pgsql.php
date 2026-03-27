<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $allowedStatuses = [
            'open',
            'assigned',
            'in_progress',
            'completed',
            'cancelled',
        ];

        if (DB::getDriverName() === 'sqlite') {
            $allowedSql = implode(", ", array_map(static fn (string $s): string => '"' . $s . '"', $allowedStatuses));

            DB::statement('DROP TABLE IF EXISTS shifts_new');

            DB::statement('
                CREATE TABLE shifts_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tenant_id INTEGER NOT NULL,
                    shift_template_id INTEGER NULL,
                    assignment_id INTEGER NULL,
                    facility_id INTEGER NULL,
                    title VARCHAR(255) NULL,
                    starts_at DATETIME NOT NULL,
                    ends_at DATETIME NOT NULL,
                    break_minutes INTEGER NOT NULL DEFAULT 0,
                    timezone VARCHAR(255) NOT NULL DEFAULT "UTC",
                    required_staff INTEGER NOT NULL DEFAULT 1,
                    status VARCHAR(255) NOT NULL DEFAULT "open" CHECK(status IN (' . $allowedSql . ')),
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    FOREIGN KEY (tenant_id) REFERENCES organizations(id),
                    FOREIGN KEY (shift_template_id) REFERENCES shift_templates(id) ON DELETE SET NULL,
                    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE SET NULL,
                    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE SET NULL
                )
            ');

            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_facility_id_index ON shifts_new(tenant_id, facility_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_status_index ON shifts_new(tenant_id, status)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_starts_at_index ON shifts_new(tenant_id, starts_at)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_assignment_id_index ON shifts_new(tenant_id, assignment_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_required_staff_index ON shifts_new(tenant_id, required_staff)');

            DB::statement('
                INSERT INTO shifts_new (
                    id,
                    tenant_id,
                    shift_template_id,
                    assignment_id,
                    facility_id,
                    title,
                    starts_at,
                    ends_at,
                    break_minutes,
                    timezone,
                    required_staff,
                    status,
                    created_at,
                    updated_at
                )
                SELECT
                    id,
                    tenant_id,
                    shift_template_id,
                    assignment_id,
                    facility_id,
                    title,
                    starts_at,
                    ends_at,
                    break_minutes,
                    timezone,
                    COALESCE(required_staff, 1),
                    status,
                    created_at,
                    updated_at
                FROM shifts
            ');

            DB::statement('DROP TABLE shifts');
            DB::statement('ALTER TABLE shifts_new RENAME TO shifts');

            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_facility_id_index ON shifts(tenant_id, facility_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_status_index ON shifts(tenant_id, status)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_starts_at_index ON shifts(tenant_id, starts_at)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_assignment_id_index ON shifts(tenant_id, assignment_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_required_staff_index ON shifts(tenant_id, required_staff)');

            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE shifts ALTER COLUMN status TYPE VARCHAR(255)');
            DB::statement("ALTER TABLE shifts ALTER COLUMN status SET DEFAULT 'open'");
            DB::statement('ALTER TABLE shifts ALTER COLUMN status SET NOT NULL');
            DB::statement('ALTER TABLE shifts DROP CONSTRAINT IF EXISTS shifts_status_check');

            $allowedSql = implode(", ", array_map(static fn (string $s): string => "'" . $s . "'", $allowedStatuses));
            DB::statement("ALTER TABLE shifts ADD CONSTRAINT shifts_status_check CHECK (status IN ({$allowedSql}))");

            return;
        }

        Schema::table('shifts', function (Blueprint $table) use ($allowedStatuses) {
            $table->enum('status', $allowedStatuses)
                ->default('open')
                ->change();
        });
    }

    public function down(): void
    {
        $allowedStatuses = [
            'open',
            'cancelled',
            'completed',
        ];

        if (DB::getDriverName() === 'sqlite') {
            $allowedSql = implode(", ", array_map(static fn (string $s): string => '"' . $s . '"', $allowedStatuses));

            DB::statement('DROP TABLE IF EXISTS shifts_old');

            DB::statement('
                CREATE TABLE shifts_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    tenant_id INTEGER NOT NULL,
                    shift_template_id INTEGER NULL,
                    assignment_id INTEGER NULL,
                    facility_id INTEGER NULL,
                    title VARCHAR(255) NULL,
                    starts_at DATETIME NOT NULL,
                    ends_at DATETIME NOT NULL,
                    break_minutes INTEGER NOT NULL DEFAULT 0,
                    timezone VARCHAR(255) NOT NULL DEFAULT "UTC",
                    required_staff INTEGER NOT NULL DEFAULT 1,
                    status VARCHAR(255) NOT NULL DEFAULT "open" CHECK(status IN (' . $allowedSql . ')),
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    FOREIGN KEY (tenant_id) REFERENCES organizations(id),
                    FOREIGN KEY (shift_template_id) REFERENCES shift_templates(id) ON DELETE SET NULL,
                    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE SET NULL,
                    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE SET NULL
                )
            ');

            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_facility_id_index ON shifts_old(tenant_id, facility_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_status_index ON shifts_old(tenant_id, status)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_starts_at_index ON shifts_old(tenant_id, starts_at)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_assignment_id_index ON shifts_old(tenant_id, assignment_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_required_staff_index ON shifts_old(tenant_id, required_staff)');

            DB::statement('
                INSERT INTO shifts_old (
                    id,
                    tenant_id,
                    shift_template_id,
                    assignment_id,
                    facility_id,
                    title,
                    starts_at,
                    ends_at,
                    break_minutes,
                    timezone,
                    required_staff,
                    status,
                    created_at,
                    updated_at
                )
                SELECT
                    id,
                    tenant_id,
                    shift_template_id,
                    assignment_id,
                    facility_id,
                    title,
                    starts_at,
                    ends_at,
                    break_minutes,
                    timezone,
                    COALESCE(required_staff, 1),
                    CASE
                        WHEN status IN ("open", "cancelled", "completed") THEN status
                        ELSE "open"
                    END,
                    created_at,
                    updated_at
                FROM shifts
            ');

            DB::statement('DROP TABLE shifts');
            DB::statement('ALTER TABLE shifts_old RENAME TO shifts');

            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_facility_id_index ON shifts(tenant_id, facility_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_status_index ON shifts(tenant_id, status)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_starts_at_index ON shifts(tenant_id, starts_at)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_assignment_id_index ON shifts(tenant_id, assignment_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS shifts_tenant_id_required_staff_index ON shifts(tenant_id, required_staff)');

            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE shifts ALTER COLUMN status TYPE VARCHAR(255)');
            DB::statement("ALTER TABLE shifts ALTER COLUMN status SET DEFAULT 'open'");
            DB::statement('ALTER TABLE shifts ALTER COLUMN status SET NOT NULL');
            DB::statement('ALTER TABLE shifts DROP CONSTRAINT IF EXISTS shifts_status_check');

            $allowedSql = implode(", ", array_map(static fn (string $s): string => "'" . $s . "'", $allowedStatuses));
            DB::statement("ALTER TABLE shifts ADD CONSTRAINT shifts_status_check CHECK (status IN ({$allowedSql}))");

            return;
        }

        Schema::table('shifts', function (Blueprint $table) use ($allowedStatuses) {
            $table->enum('status', $allowedStatuses)
                ->default('open')
                ->change();
        });
    }
};
