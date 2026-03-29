<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropUnique('users_email_unique');
            } catch (\Throwable $e) {
                // Index may already be absent in some environments.
            }

            try {
                $table->unique(['organization_id', 'email'], 'users_organization_id_email_unique');
            } catch (\Throwable $e) {
                // Index may already exist in some environments.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropUnique('users_organization_id_email_unique');
            } catch (\Throwable $e) {
                // Index may already be absent.
            }

            try {
                $table->unique('email', 'users_email_unique');
            } catch (\Throwable $e) {
                // Index may already exist.
            }
        });
    }
};
