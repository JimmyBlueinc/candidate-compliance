<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            if (Schema::hasTable('placements_old')) {
                Schema::dropIfExists('placements');
            } else {
                DB::statement('ALTER TABLE placements RENAME TO placements_old');
            }

            DB::statement('DROP INDEX IF EXISTS placements_tenant_id_index');
            DB::statement('DROP INDEX IF EXISTS placements_stage_index');
            DB::statement('DROP INDEX IF EXISTS placements_tenant_id_candidate_id_job_order_id_unique');
            DB::statement('DROP INDEX IF EXISTS placements_arrival_confirmed_at_index');

            Schema::create('placements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('organizations');
                $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
                $table->foreignId('job_order_id')->constrained('job_orders')->cascadeOnDelete();
                $table->enum('stage', ['applied', 'submitted', 'interviewing', 'offered', 'placed', 'active'])->default('applied');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamp('arrival_confirmed_at')->nullable();
                $table->timestamps();

                $table->index('tenant_id');
                $table->index('stage');
                $table->unique(['tenant_id', 'candidate_id', 'job_order_id']);
                $table->index('arrival_confirmed_at');
            });

            DB::statement("INSERT INTO placements (id, tenant_id, candidate_id, job_order_id, stage, start_date, end_date, arrival_confirmed_at, created_at, updated_at)
                SELECT id, tenant_id, candidate_id, job_order_id, stage, start_date, end_date, arrival_confirmed_at, created_at, updated_at FROM placements_old");

            DB::statement('DROP TABLE placements_old');
            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE placements MODIFY stage ENUM('applied','submitted','interviewing','offered','placed','active') NOT NULL DEFAULT 'applied'");
            return;
        }

        if ($driver === 'pgsql') {
            if (!Schema::hasTable('placements')) {
                return;
            }

            DB::statement("ALTER TABLE placements DROP CONSTRAINT IF EXISTS placements_stage_check");
            DB::statement("ALTER TABLE placements ADD CONSTRAINT placements_stage_check CHECK (stage IN ('applied', 'submitted', 'interviewing', 'offered', 'placed', 'active'))");
            return;
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            if (Schema::hasTable('placements_old')) {
                Schema::dropIfExists('placements');
            } else {
                DB::statement('ALTER TABLE placements RENAME TO placements_old');
            }

            DB::statement('DROP INDEX IF EXISTS placements_tenant_id_index');
            DB::statement('DROP INDEX IF EXISTS placements_stage_index');
            DB::statement('DROP INDEX IF EXISTS placements_tenant_id_candidate_id_job_order_id_unique');
            DB::statement('DROP INDEX IF EXISTS placements_arrival_confirmed_at_index');

            Schema::create('placements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('organizations');
                $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
                $table->foreignId('job_order_id')->constrained('job_orders')->cascadeOnDelete();
                $table->enum('stage', ['applied', 'interviewing', 'offered', 'placed', 'active'])->default('applied');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamp('arrival_confirmed_at')->nullable();
                $table->timestamps();

                $table->index('tenant_id');
                $table->index('stage');
                $table->unique(['tenant_id', 'candidate_id', 'job_order_id']);
                $table->index('arrival_confirmed_at');
            });

            DB::statement("INSERT INTO placements (id, tenant_id, candidate_id, job_order_id, stage, start_date, end_date, arrival_confirmed_at, created_at, updated_at)
                SELECT id, tenant_id, candidate_id, job_order_id,
                    CASE WHEN stage = 'submitted' THEN 'applied' ELSE stage END,
                    start_date, end_date, arrival_confirmed_at, created_at, updated_at
                FROM placements_old");

            DB::statement('DROP TABLE placements_old');
            DB::statement('PRAGMA foreign_keys = ON');
            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE placements MODIFY stage ENUM('applied','interviewing','offered','placed','active') NOT NULL DEFAULT 'applied'");
        }

        if ($driver === 'pgsql') {
            if (!Schema::hasTable('placements')) {
                return;
            }

            DB::statement("UPDATE placements SET stage = 'applied' WHERE stage = 'submitted'");
            DB::statement("ALTER TABLE placements DROP CONSTRAINT IF EXISTS placements_stage_check");
            DB::statement("ALTER TABLE placements ADD CONSTRAINT placements_stage_check CHECK (stage IN ('applied', 'interviewing', 'offered', 'placed', 'active'))");
        }
    }
};
