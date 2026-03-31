<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_interviews', function (Blueprint $table) {
            $table->json('interviewer_user_ids')->nullable()->after('scheduled_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_interviews', function (Blueprint $table) {
            $table->dropColumn('interviewer_user_ids');
        });
    }
};

