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
        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('candidate_name'); // For tracking which candidate this belongs to
            $table->string('training_name');
            $table->enum('training_type', ['mandatory', 'continuing_education', 'certification', 'workshop', 'other'])->default('mandatory');
            $table->string('provider')->nullable(); // Training provider/institution
            $table->date('completion_date')->nullable();
            $table->date('expiry_date')->nullable(); // For training that expires
            $table->decimal('credits', 5, 2)->nullable(); // CEUs or credits
            $table->string('document_path')->nullable(); // Certificate
            $table->enum('status', ['completed', 'expired', 'pending', 'in_progress'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('training_type');
            $table->index('status');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_records');
    }
};

