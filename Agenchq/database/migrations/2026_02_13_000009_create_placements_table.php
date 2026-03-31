<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations');
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('job_order_id')->constrained('job_orders')->cascadeOnDelete();
            $table->enum('stage', ['applied', 'interviewing', 'offered', 'placed', 'active'])->default('applied');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('stage');
            $table->unique(['tenant_id', 'candidate_id', 'job_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placements');
    }
};
