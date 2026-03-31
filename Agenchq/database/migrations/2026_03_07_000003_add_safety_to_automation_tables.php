<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_rules', function (Blueprint $table) {
            $table->boolean('stop_processing')->default(false)->after('priority');
        });

        Schema::table('automation_logs', function (Blueprint $table) {
            $table->text('error_message')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('automation_rules', function (Blueprint $table) {
            $table->dropColumn('stop_processing');
        });

        Schema::table('automation_logs', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
    }
};
