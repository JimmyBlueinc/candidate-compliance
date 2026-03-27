<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->integer('required_staff')->default(1)->after('timezone');
            $table->index(['tenant_id', 'required_staff']);
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE shifts MODIFY status ENUM('open','assigned','in_progress','completed','cancelled') NOT NULL DEFAULT 'open'");
        }

        if ($driver === 'pgsql') {
            // For Postgres, status enum alterations depend on enum type name; left as-is.
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE shifts MODIFY status ENUM('open','cancelled','completed') NOT NULL DEFAULT 'open'");
        }

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'required_staff']);
            $table->dropColumn('required_staff');
        });
    }
};
