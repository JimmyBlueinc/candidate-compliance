<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->index(['tenant_id', 'specialty'], 'candidates_tenant_specialty_idx');
            $table->index(['tenant_id', 'created_at'], 'candidates_tenant_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex('candidates_tenant_specialty_idx');
            $table->dropIndex('candidates_tenant_created_idx');
        });
    }
};

