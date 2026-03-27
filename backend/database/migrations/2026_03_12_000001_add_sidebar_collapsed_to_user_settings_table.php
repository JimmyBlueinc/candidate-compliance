<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("ALTER TABLE user_settings ADD COLUMN sidebar_collapsed INTEGER NOT NULL DEFAULT 0");
        } else {
            Schema::table('user_settings', function (Blueprint $table) {
                $table->boolean('sidebar_collapsed')->default(false)->after('theme');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support DROP COLUMN reliably in older versions.
            // Intentionally left as-is.
            return;
        }

        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('sidebar_collapsed');
        });
    }
};
