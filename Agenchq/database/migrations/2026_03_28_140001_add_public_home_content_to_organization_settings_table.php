<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('organization_settings', 'public_home_content')) {
                $table->json('public_home_content')->nullable()->after('module_preferences');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organization_settings', function (Blueprint $table) {
            if (Schema::hasColumn('organization_settings', 'public_home_content')) {
                $table->dropColumn('public_home_content');
            }
        });
    }
};
