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
        Schema::create('performance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('candidate_name'); // For tracking which candidate this belongs to
            $table->enum('record_type', ['performance_review', 'incident', 'attendance', 'feedback', 'disciplinary_action', 'commendation'])->default('performance_review');
            $table->date('date');
            $table->integer('rating')->nullable(); // 1-5 scale or similar
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes');
            $table->string('document_path')->nullable();
            $table->enum('status', ['active', 'resolved', 'archived'])->default('active');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('record_type');
            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_records');
    }
};

