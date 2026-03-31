<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE shift_requests MODIFY status ENUM('pending','approved','rejected','withdrawn') NOT NULL DEFAULT 'pending'");
        }

        if ($driver === 'pgsql') {
            // For Postgres, status enum alterations depend on enum type name; left as-is.
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE shift_requests MODIFY status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
