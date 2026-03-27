<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_sources', function (Blueprint $table) {
            $table->boolean('archive_missing')->default(false)->after('enabled');
            $table->json('mapping')->nullable()->after('archive_missing');

            $table->unsignedInteger('last_run_items')->nullable()->after('last_error');
            $table->unsignedInteger('last_run_upserts')->nullable()->after('last_run_items');
            $table->unsignedInteger('last_run_errors')->nullable()->after('last_run_upserts');
        });
    }

    public function down(): void
    {
        Schema::table('job_sources', function (Blueprint $table) {
            $table->dropColumn([
                'archive_missing',
                'mapping',
                'last_run_items',
                'last_run_upserts',
                'last_run_errors',
            ]);
        });
    }
};
