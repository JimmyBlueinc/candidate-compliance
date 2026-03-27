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
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('candidate_name'); // For tracking which candidate this belongs to
            $table->enum('record_type', ['immunization', 'tb_test', 'health_screening', 'medical_clearance', 'fit_for_duty'])->default('immunization');
            $table->string('vaccine_type')->nullable(); // For immunizations (COVID-19, Flu, Hepatitis B, etc.)
            $table->integer('dose_number')->nullable(); // For multi-dose vaccines
            $table->date('administration_date')->nullable();
            $table->date('expiry_date')->nullable(); // For immunizations that expire
            $table->string('provider_name')->nullable(); // Clinic/hospital name
            $table->string('document_path')->nullable();
            $table->enum('status', ['up_to_date', 'expired', 'pending', 'due'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('record_type');
            $table->index('status');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};

