<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            try {
                DB::statement('ALTER TABLE users DROP INDEX users_email_unique');
            } catch (\Throwable $e) {
                // Index may already be dropped in some environments.
            }

            try {
                DB::statement('ALTER TABLE users ADD UNIQUE KEY users_organization_email_unique (organization_id, email)');
            } catch (\Throwable $e) {
                // Index may already exist.
            }

            return;
        }

        if ($driver === 'pgsql') {
            // In PostgreSQL, Laravel's unique keys are backed by constraints.
            // Attempt constraint drop first, then index drop for edge environments.
            try {
                DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_unique');
            } catch (\Throwable $e) {
                // Fall through to index drop attempt.
            }

            try {
                DB::statement('DROP INDEX IF EXISTS users_email_unique');
            } catch (\Throwable $e) {
                // Ignore when backing object is constraint-owned.
            }

            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_organization_email_unique ON users (organization_id, email)');
            return;
        }

        // SQLite and other drivers are left unchanged to avoid destructive table rebuilds.
        // Production environments use MySQL/PostgreSQL where scoped uniqueness is applied.
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            try {
                DB::statement('ALTER TABLE users DROP INDEX users_organization_email_unique');
            } catch (\Throwable $e) {
                // Index may already be dropped.
            }

            try {
                DB::statement('ALTER TABLE users ADD UNIQUE KEY users_email_unique (email)');
            } catch (\Throwable $e) {
                // Index may already exist.
            }

            return;
        }

        if ($driver === 'pgsql') {
            try {
                DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_organization_email_unique');
            } catch (\Throwable $e) {
                // Fall through to index drop attempt.
            }

            DB::statement('DROP INDEX IF EXISTS users_organization_email_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users (email)');
        }
    }
};
