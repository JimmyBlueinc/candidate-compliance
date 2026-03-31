<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->unsignedBigInteger('submission_id')->nullable()->after('job_order_id');
            $table->index('submission_id');
            $table->foreign('submission_id')->references('id')->on('submissions')->nullOnDelete();
        });

        // Backfill: for each placement, pick earliest submission (MIN(id)) matching tenant_id + job_order_id + candidate_id
        DB::table('placements')
            ->select(['id', 'tenant_id', 'job_order_id', 'candidate_id'])
            ->orderBy('id')
            ->chunkById(500, function ($placements) {
                foreach ($placements as $p) {
                    $submissionId = DB::table('submissions')
                        ->where('tenant_id', $p->tenant_id)
                        ->where('job_order_id', $p->job_order_id)
                        ->where('candidate_id', $p->candidate_id)
                        ->orderBy('id', 'asc')
                        ->value('id');

                    if (!$submissionId) {
                        continue;
                    }

                    DB::table('placements')
                        ->where('id', $p->id)
                        ->where('tenant_id', $p->tenant_id)
                        ->update(['submission_id' => $submissionId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->dropForeign(['submission_id']);
            $table->dropIndex(['submission_id']);
            $table->dropColumn('submission_id');
        });
    }
};
