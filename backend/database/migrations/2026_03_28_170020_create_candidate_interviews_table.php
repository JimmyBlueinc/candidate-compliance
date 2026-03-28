<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_interviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('scheduled_by_user_id');
            $table->string('stage', 80)->default('interview');
            $table->string('location', 255)->nullable();
            $table->string('meeting_link', 2000)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 40)->default('scheduled'); // scheduled, completed, cancelled, no_show
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'candidate_id', 'starts_at'], 'candidate_interviews_schedule_index');
            $table->index(['tenant_id', 'status'], 'candidate_interviews_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_interviews');
    }
};

