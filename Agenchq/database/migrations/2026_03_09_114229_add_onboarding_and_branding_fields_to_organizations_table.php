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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('subdomain')->nullable()->unique()->after('slug');
            $table->timestamp('onboarding_completed_at')->nullable()->after('is_active');
            $table->string('onboarding_step')->default('subdomain')->after('onboarding_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['subdomain', 'onboarding_completed_at', 'onboarding_step']);
        });
    }
};
