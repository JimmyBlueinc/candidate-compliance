<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_pipeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations');
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->enum('stage', [
                'new',
                'screening',
                'interview',
                'credential_pending',
                'ready_to_submit',
                'submitted',
                'placed',
                'inactive',
            ])->default('new');
            $table->foreignId('assigned_recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'candidate_id']);
            $table->index(['tenant_id', 'stage']);
            $table->index(['tenant_id', 'assigned_recruiter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_pipeline');
    }
};
