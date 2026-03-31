<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('credential_id')->constrained('candidate_credentials')->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'credential_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_verifications');
    }
};
