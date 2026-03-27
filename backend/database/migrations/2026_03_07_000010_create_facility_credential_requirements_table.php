<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_credential_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignId('credential_type_id')->constrained('credential_types')->cascadeOnDelete();
            $table->boolean('required')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'facility_id', 'credential_type_id']);
            $table->index(['tenant_id', 'facility_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_credential_requirements');
    }
};
