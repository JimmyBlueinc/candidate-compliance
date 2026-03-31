<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_rate_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            
            $table->string('role_title'); // e.g., "Registered Nurse", "CNA", "LPN"
            $table->decimal('bill_rate', 12, 2)->nullable();
            $table->decimal('pay_rate', 12, 2)->nullable();
            $table->decimal('overtime_rate', 12, 2)->nullable();
            $table->decimal('holiday_rate', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('shift_type')->nullable(); // day, night, weekend
            $table->string('unit')->default('hour'); // hour, day, shift
            
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            
            // Extraction metadata
            $table->decimal('confidence_score', 3, 2)->nullable(); // 0.00 - 1.00
            $table->json('source_span')->nullable(); // {text: "...", page: 1}
            
            // Review
            $table->enum('review_status', ['pending', 'approved', 'rejected', 'modified'])->default('pending');
            $table->decimal('approved_bill_rate', 12, 2)->nullable();
            $table->decimal('approved_pay_rate', 12, 2)->nullable();
            $table->decimal('approved_overtime_rate', 12, 2)->nullable();
            $table->decimal('approved_holiday_rate', 12, 2)->nullable();
            
            $table->timestamps();
            
            $table->index(['contract_id', 'role_title']);
            $table->index(['contract_id', 'review_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_rate_lines');
    }
};
