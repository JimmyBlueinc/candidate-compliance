<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruiter_tasks', function (Blueprint $table) {
            $table->string('recurrence', 20)->default('none')->after('status'); // none,daily,weekly,monthly
            $table->unsignedInteger('recurrence_interval')->default(1)->after('recurrence');
        });
    }

    public function down(): void
    {
        Schema::table('recruiter_tasks', function (Blueprint $table) {
            $table->dropColumn(['recurrence', 'recurrence_interval']);
        });
    }
};

