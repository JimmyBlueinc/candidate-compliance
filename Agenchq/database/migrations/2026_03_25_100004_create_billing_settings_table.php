<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            
            // Billing configuration
            $table->integer('payment_terms_days')->default(30);
            $table->string('invoice_frequency')->default('weekly'); // weekly, biweekly, monthly
            $table->string('currency', 3)->default('USD');
            $table->decimal('default_bill_rate', 12, 2)->nullable();
            $table->decimal('default_pay_rate', 12, 2)->nullable();
            $table->decimal('default_markup_percent', 5, 2)->nullable();
            $table->decimal('overtime_multiplier', 4, 2)->default(1.5);
            $table->decimal('holiday_multiplier', 4, 2)->default(2.0);
            $table->boolean('timesheet_required')->default(true);
            $table->boolean('expense_allowed')->default(false);
            $table->decimal('minimum_bill_hours', 4, 2)->default(0);
            
            // Source tracking
            $table->enum('source', ['manual', 'contract'])->default('manual');
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->timestamp('applied_at')->nullable(); // When contract was applied to billing
            
            $table->timestamps();
            
            $table->unique('facility_id'); // One billing config per facility
            $table->index(['organization_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_settings');
    }
};
