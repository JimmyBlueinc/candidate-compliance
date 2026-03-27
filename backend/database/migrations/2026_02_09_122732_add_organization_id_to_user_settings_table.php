<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE user_settings ADD COLUMN organization_id INTEGER NULL');
            DB::statement('CREATE INDEX IF NOT EXISTS user_settings_organization_id_index ON user_settings(organization_id)');
        } else {
            Schema::table('user_settings', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete()->after('id');
                $table->index('organization_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS user_settings_organization_id_index');
        } else {
            Schema::table('user_settings', function (Blueprint $table) {
                $table->dropConstrainedForeignId('organization_id');
            });
        }
    }
};
