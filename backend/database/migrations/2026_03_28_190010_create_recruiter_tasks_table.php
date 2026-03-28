<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruiter_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('candidate_id')->nullable();
            $table->unsignedBigInteger('assigned_by_user_id');
            $table->unsignedBigInteger('assigned_to_user_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
            $table->string('status', 20)->default('open'); // open, in_progress, completed, cancelled
            $table->timestamp('due_at')->nullable();
            $table->timestamp('remind_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'assigned_to_user_id', 'status'], 'recruiter_tasks_assignee_status_idx');
            $table->index(['tenant_id', 'due_at'], 'recruiter_tasks_due_idx');
            $table->index(['tenant_id', 'candidate_id'], 'recruiter_tasks_candidate_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_tasks');
    }
};

