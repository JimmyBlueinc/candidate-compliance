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
            $table->string('license_type')->nullable()->after('specialty');
            $table->unsignedSmallInteger('years_experience')->nullable()->after('license_type');
            $table->string('city')->nullable()->after('years_experience');
            $table->string('state', 50)->nullable()->after('city');
            $table->string('source')->nullable()->after('state');
            $table->text('notes')->nullable()->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'license_type',
                'years_experience',
                'city',
                'state',
                'source',
                'notes',
            ]);
        });
    }
};
