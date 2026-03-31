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
        Schema::table('credentials', function (Blueprint $table) {
            if (!Schema::hasColumn('credentials', 'specialty')) {
                $table->string('specialty')->nullable()->after('position');
            }

            if (!Schema::hasColumn('credentials', 'province')) {
                $table->string('province', 50)->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            if (Schema::hasColumn('credentials', 'specialty')) {
                $table->dropColumn('specialty');
            }

            if (Schema::hasColumn('credentials', 'province')) {
                $table->dropColumn('province');
            }
        });
    }
};
