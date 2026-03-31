<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations');
            $table->foreignId('placement_id')->constrained('placements')->cascadeOnDelete();
            $table->enum('type', ['flight', 'drive', 'hotel']);
            $table->text('details')->nullable();
            $table->string('confirmation_number')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('placement_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_logs');
    }
};
