<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations');
            $table->string('title');
            $table->string('facility_name');
            $table->string('specialty')->nullable();
            $table->decimal('bill_rate', 10, 2)->nullable();
            $table->decimal('pay_rate', 10, 2)->nullable();
            $table->enum('status', ['open', 'filled', 'closed'])->default('open');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
            $table->index('specialty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};
