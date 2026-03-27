<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('event');
            $table->boolean('enabled')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'event', 'enabled']);
            $table->index(['tenant_id', 'priority']);
        });

        Schema::create('automation_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->string('field');
            $table->string('operator');
            $table->string('value');

            $table->index('rule_id');
        });

        Schema::create('automation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('automation_rules')->cascadeOnDelete();
            $table->string('action');
            $table->json('config')->nullable();
            $table->unsignedInteger('order')->default(0);

            $table->index(['rule_id', 'order']);
        });

        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->nullable()->constrained('automation_rules')->nullOnDelete();
            $table->foreignId('tenant_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('event');
            $table->string('status');
            $table->timestamp('executed_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'event', 'executed_at']);
            $table->index(['rule_id', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('automation_actions');
        Schema::dropIfExists('automation_conditions');
        Schema::dropIfExists('automation_rules');
    }
};
