<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('organizations')->cascadeOnDelete();
            $table->index(['tenant_id']);
        });

        $deliveries = DB::table('webhook_deliveries')
            ->select(['webhook_deliveries.id', 'webhook_endpoints.tenant_id as endpoint_tenant_id'])
            ->join('webhook_endpoints', 'webhook_endpoints.id', '=', 'webhook_deliveries.webhook_endpoint_id')
            ->get();

        foreach ($deliveries as $delivery) {
            DB::table('webhook_deliveries')
                ->where('id', $delivery->id)
                ->update(['tenant_id' => $delivery->endpoint_tenant_id]);
        }

        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable(false)->change();
        });

        Schema::table('email_jobs', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('recipient');
            $table->longText('body')->nullable()->after('subject');
        });

        Schema::table('communication_logs', function (Blueprint $table) {
            $table->string('event')->nullable()->after('type');
            $table->index(['tenant_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'event']);
            $table->dropColumn('event');
        });

        Schema::table('email_jobs', function (Blueprint $table) {
            $table->dropColumn(['subject', 'body']);
        });

        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
