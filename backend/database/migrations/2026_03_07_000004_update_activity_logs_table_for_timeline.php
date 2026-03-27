<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The existing activity_logs table is very old (2025) and lacks tenant_id.
        // We will add the missing columns to make it compatible with the new requirements.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('organizations')->cascadeOnDelete();
            $table->string('event')->nullable()->after('entity_id');
            $table->renameColumn('metadata', 'data');
            $table->renameColumn('action', 'old_action');
            $table->renameColumn('entity', 'entity_type');
            
            $table->index(['tenant_id', 'event']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'event']);
            $table->dropIndex(['entity_type', 'entity_id']);
            
            $table->renameColumn('data', 'metadata');
            $table->renameColumn('old_action', 'action');
            $table->renameColumn('entity_type', 'entity');
            $table->dropColumn(['tenant_id', 'event']);
        });
    }
};
