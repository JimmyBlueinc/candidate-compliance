<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('tenant_id');
            $table->string('source', 80)->nullable()->after('external_id');
            $table->boolean('published')->default(false)->after('pay_rate');
            $table->date('start_date')->nullable()->after('published');
            $table->enum('work_mode', ['remote', 'on_site'])->default('on_site')->after('start_date');
            $table->decimal('stipend_weekly', 10, 2)->nullable()->after('work_mode');

            $table->index(['source', 'external_id']);
            $table->index('published');
            $table->index('start_date');
            $table->index('work_mode');
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropIndex(['source', 'external_id']);
            $table->dropIndex(['published']);
            $table->dropIndex(['start_date']);
            $table->dropIndex(['work_mode']);

            $table->dropColumn([
                'external_id',
                'source',
                'published',
                'start_date',
                'work_mode',
                'stipend_weekly',
            ]);
        });
    }
};
