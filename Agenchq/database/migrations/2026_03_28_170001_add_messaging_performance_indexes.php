<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['tenant_id', 'user_id', 'recipient_id', 'id'], 'messages_direct_thread_index');
            $table->index(['tenant_id', 'recipient_id', 'read_at', 'id'], 'messages_recipient_unread_index');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_direct_thread_index');
            $table->dropIndex('messages_recipient_unread_index');
        });
    }
};

