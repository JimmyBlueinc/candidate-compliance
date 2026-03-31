<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("ALTER TABLE users ADD COLUMN access_status VARCHAR(20) NOT NULL DEFAULT 'active'");
            DB::statement("CREATE INDEX IF NOT EXISTS users_access_status_index ON users(access_status)");
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('access_status', ['active', 'suspended', 'terminated'])->default('active')->after('role');
            $table->index('access_status');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS users_access_status_index');
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['access_status']);
            $table->dropColumn('access_status');
        });
    }
};
