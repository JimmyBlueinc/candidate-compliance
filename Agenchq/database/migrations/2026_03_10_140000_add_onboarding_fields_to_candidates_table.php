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
        Schema::table('candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('candidates', 'address_line1')) {
                $table->string('address_line1')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('candidates', 'address_line2')) {
                $table->string('address_line2')->nullable()->after('address_line1');
            }
            if (!Schema::hasColumn('candidates', 'postal_code')) {
                $table->string('postal_code', 30)->nullable()->after('state');
            }
            if (!Schema::hasColumn('candidates', 'country')) {
                $table->string('country', 100)->nullable()->after('postal_code');
            }

            if (!Schema::hasColumn('candidates', 'work_authorization')) {
                $table->boolean('work_authorization')->nullable()->after('country');
            }
            if (!Schema::hasColumn('candidates', 'background_check')) {
                $table->boolean('background_check')->nullable()->after('work_authorization');
            }
            if (!Schema::hasColumn('candidates', 'drug_screen')) {
                $table->boolean('drug_screen')->nullable()->after('background_check');
            }
            if (!Schema::hasColumn('candidates', 'vaccination')) {
                $table->boolean('vaccination')->nullable()->after('drug_screen');
            }

            if (!Schema::hasColumn('candidates', 'onboarding_completed_at')) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('vaccination');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'address_line1',
                'address_line2',
                'postal_code',
                'country',
                'work_authorization',
                'background_check',
                'drug_screen',
                'vaccination',
                'onboarding_completed_at',
            ]);
        });
    }
};
