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
            $table->string('first_name')->nullable()->after('user_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('specialty')->nullable()->after('phone');
            $table->json('tags')->nullable()->after('specialty');
            $table->dateTime('last_applied_at')->nullable()->after('tags');
            $table->string('resume_path')->nullable()->after('last_applied_at');

            $table->index('last_applied_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex(['last_applied_at']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'specialty',
                'tags',
                'last_applied_at',
                'resume_path',
            ]);
        });
    }
};
