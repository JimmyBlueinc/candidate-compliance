<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->string('role')->nullable()->after('title');
            $table->integer('required_staff')->default(1)->after('role');
            $table->date('end_date')->nullable()->after('start_date');
            $table->text('description')->nullable()->after('end_date');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('description');

            $table->index(['tenant_id', 'status']);
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("UPDATE job_orders SET status = 'cancelled' WHERE status = 'closed'");
            DB::statement("ALTER TABLE job_orders MODIFY status ENUM('open','in_progress','filled','cancelled') NOT NULL DEFAULT 'open'");
        }

        if ($driver === 'pgsql') {
            // For Postgres, status enum alterations depend on enum type name; left as-is.
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("UPDATE job_orders SET status = 'closed' WHERE status = 'cancelled'");
            DB::statement("UPDATE job_orders SET status = 'open' WHERE status = 'in_progress'");
            DB::statement("ALTER TABLE job_orders MODIFY status ENUM('open','filled','closed') NOT NULL DEFAULT 'open'");
        }

        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropColumn([
                'role',
                'required_staff',
                'end_date',
                'description',
            ]);
        });
    }
};
