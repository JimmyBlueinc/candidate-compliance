<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            
            // Extracted terms
            $table->integer('payment_terms_days')->nullable();
            $table->string('invoice_frequency')->nullable(); // weekly, biweekly, monthly
            $table->string('currency', 3)->default('USD');
            $table->string('bill_rate_type')->nullable(); // hourly, daily, flat
            $table->decimal('bill_rate_amount', 12, 2)->nullable();
            $table->decimal('pay_rate_amount', 12, 2)->nullable();
            $table->decimal('markup_percent', 5, 2)->nullable();
            $table->decimal('overtime_multiplier', 4, 2)->nullable();
            $table->decimal('holiday_multiplier', 4, 2)->nullable();
            $table->boolean('timesheet_required')->nullable();
            $table->boolean('expense_allowed')->nullable();
            $table->decimal('minimum_bill_hours', 4, 2)->nullable();
            
            // Extraction metadata
            $table->json('confidence_json')->nullable(); // {field: confidence_score}
            $table->json('source_spans_json')->nullable(); // {field: {text: "...", page: 1, position: [x,y]}}
            
            // Review status
            $table->enum('review_status', ['pending', 'approved', 'rejected', 'modified'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            
            // Approved values (user-modified)
            $table->integer('approved_payment_terms_days')->nullable();
            $table->string('approved_invoice_frequency')->nullable();
            $table->decimal('approved_bill_rate_amount', 12, 2)->nullable();
            $table->decimal('approved_pay_rate_amount', 12, 2)->nullable();
            $table->decimal('approved_markup_percent', 5, 2)->nullable();
            $table->decimal('approved_overtime_multiplier', 4, 2)->nullable();
            $table->decimal('approved_holiday_multiplier', 4, 2)->nullable();
            $table->boolean('approved_timesheet_required')->nullable();
            $table->boolean('approved_expense_allowed')->nullable();
            $table->decimal('approved_minimum_bill_hours', 4, 2)->nullable();
            
            $table->timestamps();
            
            $table->index(['contract_id', 'review_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_terms');
    }
};
