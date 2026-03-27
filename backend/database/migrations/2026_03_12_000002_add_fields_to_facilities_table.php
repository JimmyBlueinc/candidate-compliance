<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('postal_code')->nullable()->after('country');
            $table->string('timezone')->nullable()->after('postal_code');
            $table->string('facility_type')->nullable()->after('timezone');
            $table->string('facility_type_other')->nullable()->after('facility_type');
            $table->string('contact_person_name')->nullable()->after('facility_type_other');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn([
                'postal_code',
                'timezone',
                'facility_type',
                'facility_type_other',
                'contact_person_name',
            ]);
        });
    }
};
