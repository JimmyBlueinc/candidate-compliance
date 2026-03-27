<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('credential_id')->nullable()->constrained('credentials')->nullOnDelete()->after('candidate_id');
            $table->string('review_status')->nullable()->after('expiry_date');
            $table->text('rejection_reason')->nullable()->after('review_status');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('rejection_reason');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');

            $table->index('credential_id');
            $table->index('review_status');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['credential_id']);
            $table->dropIndex(['review_status']);

            $table->dropConstrainedForeignId('credential_id');
            $table->dropColumn('review_status');
            $table->dropColumn('rejection_reason');
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn('reviewed_at');
        });
    }
};
