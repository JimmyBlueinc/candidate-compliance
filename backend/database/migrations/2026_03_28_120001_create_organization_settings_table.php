<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete()->unique();
            $table->string('language')->default('en');
            $table->string('timezone')->default('UTC');
            $table->boolean('sidebar_collapsed')->default(false);
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('email_notifications_enabled')->default(true);
            $table->boolean('expiry_reminders_enabled')->default(true);
            $table->unsignedSmallInteger('reminder_days_before')->default(30);
            $table->json('module_preferences')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_settings');
    }
};
