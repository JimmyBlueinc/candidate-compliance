<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->timestamp('arrival_confirmed_at')->nullable()->after('end_date');
            $table->index('arrival_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->dropIndex(['arrival_confirmed_at']);
            $table->dropColumn('arrival_confirmed_at');
        });
    }
};
