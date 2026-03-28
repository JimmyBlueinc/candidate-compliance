<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_job_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('job_order_id');
            $table->timestamps();

            $table->unique(['tenant_id', 'candidate_id', 'job_order_id'], 'candidate_job_bookmarks_unique');
            $table->index(['tenant_id', 'candidate_id'], 'candidate_job_bookmarks_candidate_index');
            $table->index(['tenant_id', 'job_order_id'], 'candidate_job_bookmarks_job_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_job_bookmarks');
    }
};

