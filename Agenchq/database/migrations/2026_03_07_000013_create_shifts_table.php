<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations');
            $table->foreignId('shift_template_id')->nullable()->constrained('shift_templates')->nullOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
            $table->string('title')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->integer('break_minutes')->default(0);
            $table->string('timezone')->default('UTC');
            $table->enum('status', ['open', 'cancelled', 'completed'])->default('open');
            $table->timestamps();

            $table->index(['tenant_id', 'facility_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'starts_at']);
            $table->index(['tenant_id', 'assignment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
