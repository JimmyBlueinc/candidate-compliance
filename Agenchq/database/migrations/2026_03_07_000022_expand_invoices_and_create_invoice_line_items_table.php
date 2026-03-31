<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('invoices', 'invoice_number')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('invoice_number')->nullable()->after('facility_id');
            });
        }

        if (!Schema::hasColumn('invoices', 'issued_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->timestamp('issued_at')->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn('invoices', 'due_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->timestamp('due_at')->nullable()->after('issued_at');
            });
        }

        if (!Schema::hasColumn('invoices', 'created_by_user_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('due_at');
            });
        }

        // SQLite will throw if the index already exists (common in partially-migrated dev DBs).
        // Make this idempotent.
        $driver = DB::connection()->getDriverName();
        $indexName = 'invoices_tenant_id_facility_id_index';
        $shouldCreateIndex = true;

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('invoices')");
            foreach ($indexes as $idx) {
                if (($idx->name ?? null) === $indexName) {
                    $shouldCreateIndex = false;
                    break;
                }
            }
        }

        if ($driver === 'pgsql') {
            $indexes = DB::select(
                "SELECT indexname FROM pg_indexes WHERE schemaname = current_schema() AND tablename = 'invoices'"
            );
            foreach ($indexes as $idx) {
                if (($idx->indexname ?? null) === $indexName) {
                    $shouldCreateIndex = false;
                    break;
                }
            }
        }

        if ($shouldCreateIndex) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['tenant_id', 'facility_id'], 'invoices_tenant_id_facility_id_index');
            });
        }

        if (!Schema::hasTable('invoice_line_items')) {
            Schema::create('invoice_line_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->foreignId('timesheet_id')->constrained('timesheets')->cascadeOnDelete();
                $table->foreignId('placement_id')->nullable()->constrained('placements')->nullOnDelete();
                $table->decimal('hours', 10, 2)->default(0);
                $table->decimal('bill_rate', 10, 2)->default(0);
                $table->decimal('amount', 12, 2)->default(0);
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['invoice_id', 'timesheet_id']);
                $table->index(['invoice_id', 'placement_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');

        $driver = DB::connection()->getDriverName();
        $indexName = 'invoices_tenant_id_facility_id_index';
        $shouldDropIndex = true;

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('invoices')");
            $shouldDropIndex = false;
            foreach ($indexes as $idx) {
                if (($idx->name ?? null) === $indexName) {
                    $shouldDropIndex = true;
                    break;
                }
            }
        }

        if ($driver === 'pgsql') {
            $indexes = DB::select(
                "SELECT indexname FROM pg_indexes WHERE schemaname = current_schema() AND tablename = 'invoices'"
            );
            $shouldDropIndex = false;
            foreach ($indexes as $idx) {
                if (($idx->indexname ?? null) === $indexName) {
                    $shouldDropIndex = true;
                    break;
                }
            }
        }

        Schema::table('invoices', function (Blueprint $table) use ($shouldDropIndex) {
            // Use explicit name to match up(). (Safer on SQLite)
            // Only drop if it exists on the current driver.
            // Note: MySQL's dropIndex is fine even if the index is missing, but SQLite/PG will throw.
            // This check avoids failed rollbacks in dev/prod.
            if ($shouldDropIndex) {
                $table->dropIndex('invoices_tenant_id_facility_id_index');
            }
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropColumn([
                'invoice_number',
                'issued_at',
                'due_at',
            ]);
        });
    }
};
