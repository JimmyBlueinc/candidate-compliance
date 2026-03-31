<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->foreignId('recruiter_id')
                ->nullable()
                ->after('job_order_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('recruiter_id');
        });
    }

    public function down(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->dropIndex(['recruiter_id']);
            $table->dropConstrainedForeignId('recruiter_id');
        });
    }
};
