<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $allowedRoles = [
            'platform_admin',
            'org_super_admin',
            'admin',
            'org_owner',
            'recruiter',
            'compliance',
            'finance',
            'travel',
            'candidate',
            'facility',
        ];

        if (DB::getDriverName() === 'sqlite') {
            $allowedRolesSql = implode(", ", array_map(static fn (string $role): string => '"' . $role . '"', $allowedRoles));

            DB::statement('DROP TABLE IF EXISTS users_new');

            DB::statement('
                CREATE TABLE users_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    organization_id INTEGER NULL,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    email_verified_at DATETIME NULL,
                    password VARCHAR(255) NOT NULL,
                    role VARCHAR(255) NOT NULL DEFAULT "candidate" CHECK(role IN (' . $allowedRolesSql . ')),
                    access_status VARCHAR(20) NOT NULL DEFAULT "active",
                    avatar_path VARCHAR(255) NULL,
                    must_change_password INTEGER NOT NULL DEFAULT 0,
                    remember_token VARCHAR(100) NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                )
            ');

            DB::statement('CREATE INDEX IF NOT EXISTS users_organization_id_index ON users_new(organization_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_access_status_index ON users_new(access_status)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_must_change_password_index ON users_new(must_change_password)');

            DB::statement('
                INSERT INTO users_new (
                    id,
                    organization_id,
                    name,
                    email,
                    email_verified_at,
                    password,
                    role,
                    access_status,
                    avatar_path,
                    must_change_password,
                    remember_token,
                    created_at,
                    updated_at
                )
                SELECT
                    id,
                    organization_id,
                    name,
                    email,
                    email_verified_at,
                    password,
                    role,
                    access_status,
                    avatar_path,
                    must_change_password,
                    remember_token,
                    created_at,
                    updated_at
                FROM users
            ');

            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_new RENAME TO users');

            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users(email)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_organization_id_index ON users(organization_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_access_status_index ON users(access_status)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_must_change_password_index ON users(must_change_password)');

            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(255)');
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'candidate'");
            DB::statement('ALTER TABLE users ALTER COLUMN role SET NOT NULL');
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

            $allowedRolesSql = implode(", ", array_map(static fn (string $role): string => "'" . $role . "'", $allowedRoles));
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ({$allowedRolesSql}))");

            return;
        }

        Schema::table('users', function (Blueprint $table) use ($allowedRoles) {
            $table->enum('role', $allowedRoles)
                ->default('candidate')
                ->change();
        });
    }

    public function down(): void
    {
        $allowedRoles = [
            'platform_admin',
            'org_super_admin',
            'admin',
            'candidate',
        ];

        if (DB::getDriverName() === 'sqlite') {
            $allowedRolesSql = implode(", ", array_map(static fn (string $role): string => '"' . $role . '"', $allowedRoles));

            DB::statement('DROP TABLE IF EXISTS users_old');

            DB::statement('
                CREATE TABLE users_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    organization_id INTEGER NULL,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    email_verified_at DATETIME NULL,
                    password VARCHAR(255) NOT NULL,
                    role VARCHAR(255) NOT NULL DEFAULT "candidate" CHECK(role IN (' . $allowedRolesSql . ')),
                    access_status VARCHAR(20) NOT NULL DEFAULT "active",
                    avatar_path VARCHAR(255) NULL,
                    must_change_password INTEGER NOT NULL DEFAULT 0,
                    remember_token VARCHAR(100) NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                )
            ');

            DB::statement('CREATE INDEX IF NOT EXISTS users_organization_id_index ON users_old(organization_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_access_status_index ON users_old(access_status)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_must_change_password_index ON users_old(must_change_password)');

            DB::statement('
                INSERT INTO users_old (
                    id,
                    organization_id,
                    name,
                    email,
                    email_verified_at,
                    password,
                    role,
                    access_status,
                    avatar_path,
                    must_change_password,
                    remember_token,
                    created_at,
                    updated_at
                )
                SELECT
                    id,
                    organization_id,
                    name,
                    email,
                    email_verified_at,
                    password,
                    role,
                    access_status,
                    avatar_path,
                    must_change_password,
                    remember_token,
                    created_at,
                    updated_at
                FROM users
            ');

            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_old RENAME TO users');

            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users(email)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_organization_id_index ON users(organization_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_access_status_index ON users(access_status)');
            DB::statement('CREATE INDEX IF NOT EXISTS users_must_change_password_index ON users(must_change_password)');

            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(255)');
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'candidate'");
            DB::statement('ALTER TABLE users ALTER COLUMN role SET NOT NULL');
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

            $allowedRolesSql = implode(", ", array_map(static fn (string $role): string => "'" . $role . "'", $allowedRoles));
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ({$allowedRolesSql}))");

            return;
        }

        Schema::table('users', function (Blueprint $table) use ($allowedRoles) {
            $table->enum('role', $allowedRoles)
                ->default('candidate')
                ->change();
        });
    }
};
