<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->enum('document_type', ['msa', 'sow', 'amendment', 'rate_card'])->default('msa');
            $table->string('file_path')->nullable(); // S3 path in private_assets
            $table->string('file_name')->nullable();
            $table->integer('version')->default(1);
            $table->enum('status', ['uploaded', 'processing', 'processed', 'reviewed', 'approved'])->default('uploaded');
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('extracted_text')->nullable(); // Raw extracted text from document
            $table->timestamps();

            $table->index(['facility_id', 'status']);
            $table->index(['organization_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
