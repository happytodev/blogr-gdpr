<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogr_gdpr_consent_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('consent_type');
            $table->boolean('consent_given')->default(false);
            $table->json('consent_data')->nullable();
            $table->timestamps();
        });

        Schema::create('blogr_gdpr_requests', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('request_type');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('blogr_gdpr_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogr_gdpr_consent_logs');
        Schema::dropIfExists('blogr_gdpr_requests');
        Schema::dropIfExists('blogr_gdpr_settings');
    }
};
