<?php

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultOrg = Organization::query()->firstOrCreate(
            ['slug' => 'default'],
            ['name' => 'Default Candidate', 'is_active' => true]
        );

        Schema::table('credentials', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained('organizations')->nullOnDelete()->after('organization_id');
            $table->index('tenant_id');
        });

        DB::table('credentials')
            ->whereNull('tenant_id')
            ->update(['tenant_id' => $defaultOrg->id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
