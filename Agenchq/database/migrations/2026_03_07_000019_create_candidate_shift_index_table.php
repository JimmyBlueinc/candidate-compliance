<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_shift_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations');
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->string('role')->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->string('credential_status')->default('unknown');
            $table->timestamp('updated_at')->nullable();

            $table->index(['tenant_id', 'facility_id', 'date']);
            $table->index(['tenant_id', 'candidate_id']);
            $table->index(['tenant_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_shift_index');
    }
};
