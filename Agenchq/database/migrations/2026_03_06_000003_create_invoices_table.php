<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations');
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->string('facility_name')->nullable();
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->decimal('total_hours', 10, 2)->default(0);
            $table->decimal('bill_rate', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'issued', 'paid', 'cancelled'])->default('draft');
            $table->timestamps();

            $table->unique(['tenant_id', 'assignment_id', 'week_start_date']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
