<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('timesheets', 'facility_approved_at')) {
                $table->timestamp('facility_approved_at')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('timesheets', 'facility_approved_by_user_id')) {
                $table->foreignId('facility_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('facility_approved_at');
            }
            if (!Schema::hasColumn('timesheets', 'agency_approved_at')) {
                $table->timestamp('agency_approved_at')->nullable()->after('facility_approved_by_user_id');
            }
            if (!Schema::hasColumn('timesheets', 'agency_approved_by_user_id')) {
                $table->foreignId('agency_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('agency_approved_at');
            }
            if (!Schema::hasColumn('timesheets', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('agency_approved_by_user_id');
            }
            if (!Schema::hasColumn('timesheets', 'rejected_by_user_id')) {
                $table->foreignId('rejected_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('rejected_at');
            }
        });

        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE timesheets MODIFY COLUMN status ENUM('draft', 'submitted', 'facility_approved', 'agency_approved', 'rejected') NOT NULL DEFAULT 'draft'");

            // Migrate legacy status if present.
            DB::statement("UPDATE timesheets SET status = 'facility_approved' WHERE status = 'supervisor_approved'");
            DB::statement("UPDATE timesheets SET status = 'agency_approved' WHERE status = 'approved'");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE timesheets MODIFY COLUMN status ENUM('draft', 'submitted', 'approved', 'rejected') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('timesheets', 'facility_approved_by_user_id')) {
                $table->dropConstrainedForeignId('facility_approved_by_user_id');
            }
            if (Schema::hasColumn('timesheets', 'agency_approved_by_user_id')) {
                $table->dropConstrainedForeignId('agency_approved_by_user_id');
            }
            if (Schema::hasColumn('timesheets', 'rejected_by_user_id')) {
                $table->dropConstrainedForeignId('rejected_by_user_id');
            }

            if (Schema::hasColumn('timesheets', 'facility_approved_at')) {
                $table->dropColumn('facility_approved_at');
            }
            if (Schema::hasColumn('timesheets', 'agency_approved_at')) {
                $table->dropColumn('agency_approved_at');
            }
            if (Schema::hasColumn('timesheets', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
        });
    }
};
