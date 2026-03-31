<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('approved_at');
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_at');

            $table->dropUnique(['shift_id']);
            $table->unique(['shift_id', 'candidate_id']);

            $table->index(['tenant_id', 'shift_id']);
        });
    }

    public function down(): void
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'shift_id']);

            $table->dropUnique(['shift_id', 'candidate_id']);
            $table->unique(['shift_id']);

            $table->dropColumn('checked_in_at');
            $table->dropColumn('checked_out_at');
        });
    }
};
