<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('created_by_user_id');
            $table->text('body');
            $table->timestamps();

            $table->index(['tenant_id', 'candidate_id']);
            $table->index(['tenant_id', 'created_by_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_notes');
    }
};

