<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('language', 10)->default('en');
            $table->string('timezone', 50)->default('UTC');
            $table->string('theme', 20)->default('light'); // light, dark, auto
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('email_notifications_enabled')->default(true);
            $table->boolean('expiry_reminders_enabled')->default(true);
            $table->integer('reminder_days_before')->default(30); // Days before expiry to send reminder
            $table->json('notification_preferences')->nullable(); // Additional notification settings
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
