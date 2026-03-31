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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('facility_id')->nullable()->constrained('facilities');
            $table->foreignId('job_order_id')->nullable()->constrained('job_orders');
            $table->foreignId('submission_id')->nullable()->constrained('submissions');
            $table->foreignId('placement_id')->nullable()->constrained('placements');
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();

            // Constraints: Only one of job_order_id, submission_id, or placement_id may be set.
            // This is handled at the application level, but we add indices for performance.
            $table->index(['tenant_id', 'job_order_id']);
            $table->index(['tenant_id', 'submission_id']);
            $table->index(['tenant_id', 'placement_id']);
            $table->index(['tenant_id', 'facility_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
