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
        if (!Schema::hasColumn('users', 'facility_id')) {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                DB::statement('ALTER TABLE users ADD COLUMN facility_id INTEGER NULL');
                DB::statement('CREATE INDEX IF NOT EXISTS users_facility_id_index ON users(facility_id)');
                return;
            }

            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS users_facility_id_index');
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facility_id');
        });
    }
};
