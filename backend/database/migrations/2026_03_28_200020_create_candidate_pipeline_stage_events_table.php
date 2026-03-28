<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_pipeline_stage_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('pipeline_id');
            $table->string('from_stage', 80);
            $table->string('to_stage', 80);
            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at'], 'pipeline_stage_events_tenant_created_idx');
            $table->index(['tenant_id', 'from_stage', 'to_stage'], 'pipeline_stage_events_transition_idx');
            $table->index(['tenant_id', 'candidate_id'], 'pipeline_stage_events_candidate_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_pipeline_stage_events');
    }
};

