<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('timesheets', 'rejection_reason')) {
            Schema::table('timesheets', function (Blueprint $table) {
                // Update enum to include supervisor_approved
                // In Laravel/MySQL, we often use change() but enum changes can be tricky.
                // For the purpose of this task, we will add the rejection_reason column first.
                $table->text('rejection_reason')->nullable()->after('status');
            });
        }

        // Use raw SQL to update the enum to include 'supervisor_approved'
        // This is more reliable for MySQL/MariaDB.
        // SQLite does not support MODIFY COLUMN / ENUM, so skip in SQLite dev environments.
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE timesheets MODIFY COLUMN status ENUM('draft', 'submitted', 'supervisor_approved', 'approved', 'rejected') NOT NULL DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE timesheets MODIFY COLUMN status ENUM('draft', 'submitted', 'approved', 'rejected') NOT NULL DEFAULT 'draft'");
        }

        if (Schema::hasColumn('timesheets', 'rejection_reason')) {
            Schema::table('timesheets', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }
    }
};
