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
        // SQLite doesn't support altering CHECK constraints directly
        // We need to recreate the column with the correct constraint
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, we need to recreate the table
            // First, create a temporary table with the correct constraint
            DB::statement('
                CREATE TABLE users_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    email_verified_at DATETIME NULL,
                    password VARCHAR(255) NOT NULL,
                    role VARCHAR(255) NOT NULL DEFAULT "candidate" CHECK(role IN ("platform_admin", "org_super_admin", "admin", "candidate")),
                    avatar_path VARCHAR(255) NULL,
                    remember_token VARCHAR(100) NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                )
            ');
            
            // Copy data from old table to new table
            DB::statement('
                INSERT INTO users_new (id, name, email, email_verified_at, password, role, avatar_path, remember_token, created_at, updated_at)
                SELECT id, name, email, email_verified_at, password, role, avatar_path, remember_token, created_at, updated_at
                FROM users
            ');
            
            // Drop old table
            DB::statement('DROP TABLE users');
            
            // Rename new table
            DB::statement('ALTER TABLE users_new RENAME TO users');
            
            // Recreate indexes
            DB::statement('CREATE UNIQUE INDEX users_email_unique ON users(email)');
        } else {
            // For other databases (MySQL, PostgreSQL), we can alter the column
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['platform_admin', 'org_super_admin', 'admin', 'candidate'])
                    ->default('candidate')
                    ->change();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Revert to previous constraint (without candidate)
            DB::statement('
                CREATE TABLE users_old (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    email_verified_at DATETIME NULL,
                    password VARCHAR(255) NOT NULL,
                    role VARCHAR(255) NOT NULL DEFAULT "candidate" CHECK(role IN ("platform_admin", "org_super_admin", "admin", "candidate")),
                    avatar_path VARCHAR(255) NULL,
                    remember_token VARCHAR(100) NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL
                )
            ');
            
            DB::statement('
                INSERT INTO users_old (id, name, email, email_verified_at, password, role, avatar_path, remember_token, created_at, updated_at)
                SELECT id, name, email, email_verified_at, password, role, avatar_path, remember_token, created_at, updated_at
                FROM users
            ');
            
            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_old RENAME TO users');
            DB::statement('CREATE UNIQUE INDEX users_email_unique ON users(email)');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['platform_admin', 'org_super_admin', 'admin', 'candidate'])
                    ->default('candidate')
                    ->change();
        });
        }
    }
};
