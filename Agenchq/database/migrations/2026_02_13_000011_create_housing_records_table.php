<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations');
            $table->foreignId('placement_id')->constrained('placements')->cascadeOnDelete();
            $table->string('address')->nullable();
            $table->string('landlord_contact')->nullable();
            $table->date('lease_start')->nullable();
            $table->date('lease_end')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('placement_id');
            $table->unique(['tenant_id', 'placement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_records');
    }
};
