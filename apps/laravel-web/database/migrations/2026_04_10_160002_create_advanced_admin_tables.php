<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('setting_key', 120)->unique();
            $table->json('setting_value')->nullable();
            $table->string('group_name', 80)->default('general');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('permission_key', 120)->unique();
            $table->string('label', 150);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('role', 40);
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role', 'permission_id']);
        });

        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('certificate_no', 80)->unique();
            $table->dateTime('issued_at')->nullable();
            $table->string('status', 20)->default('generated');
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('event_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('not_submitted');
            $table->string('response_link', 500)->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_evaluations');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('settings');
    }
};
