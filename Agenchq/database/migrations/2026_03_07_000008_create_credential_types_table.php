<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->boolean('requires_expiration')->default(false);
            $table->boolean('requires_document')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'category']);
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_types');
    }
};
