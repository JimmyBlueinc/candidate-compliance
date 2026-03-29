<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_drive_files')) {
            return;
        }

        if (!Schema::hasColumn('user_drive_files', 'storage_disk')) {
            Schema::table('user_drive_files', function (Blueprint $table): void {
                $table->string('storage_disk', 80)->nullable()->after('path');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_drive_files')) {
            return;
        }

        if (Schema::hasColumn('user_drive_files', 'storage_disk')) {
            Schema::table('user_drive_files', function (Blueprint $table): void {
                $table->dropColumn('storage_disk');
            });
        }
    }
};
