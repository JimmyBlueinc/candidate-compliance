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
        Schema::create('references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('candidate_name'); // For tracking which candidate this belongs to
            $table->string('reference_name');
            $table->enum('reference_type', ['professional', 'personal', 'academic'])->default('professional');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('organization')->nullable();
            $table->string('relationship')->nullable(); // e.g., "Former Supervisor", "Colleague"
            $table->enum('verification_status', ['pending', 'verified', 'failed', 'not_contacted'])->default('pending');
            $table->date('verified_date')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable(); // Feedback from reference
            $table->date('re_verification_date')->nullable(); // When to re-verify
            $table->timestamps();
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('verification_status');
            $table->index('reference_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('references');
    }
};

