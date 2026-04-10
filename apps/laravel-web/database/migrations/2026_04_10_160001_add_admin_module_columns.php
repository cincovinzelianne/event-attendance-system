<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });

        Schema::table('events', function (Blueprint $table): void {
            if (! Schema::hasColumn('events', 'status')) {
                $table->string('status', 20)->default('upcoming')->after('attendance_locked');
            }

            if (! Schema::hasColumn('events', 'google_form_url')) {
                $table->string('google_form_url', 500)->nullable()->after('location');
            }

            if (! Schema::hasColumn('events', 'poster_path')) {
                $table->string('poster_path', 500)->nullable()->after('google_form_url');
            }

            if (! Schema::hasColumn('events', 'attachment_path')) {
                $table->string('attachment_path', 500)->nullable()->after('poster_path');
            }

            if (! Schema::hasColumn('events', 'target_department')) {
                $table->string('target_department', 120)->nullable()->after('attachment_path');
            }

            if (! Schema::hasColumn('events', 'target_course')) {
                $table->string('target_course', 120)->nullable()->after('target_department');
            }

            if (! Schema::hasColumn('events', 'target_year_level')) {
                $table->string('target_year_level', 40)->nullable()->after('target_course');
            }

            if (! Schema::hasColumn('events', 'checkin_start_at')) {
                $table->dateTime('checkin_start_at')->nullable()->after('starts_at');
            }

            if (! Schema::hasColumn('events', 'checkin_end_at')) {
                $table->dateTime('checkin_end_at')->nullable()->after('checkin_start_at');
            }
        });

        Schema::table('notifications', function (Blueprint $table): void {
            if (! Schema::hasColumn('notifications', 'scheduled_for')) {
                $table->dateTime('scheduled_for')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            if (Schema::hasColumn('notifications', 'scheduled_for')) {
                $table->dropColumn('scheduled_for');
            }
        });

        Schema::table('events', function (Blueprint $table): void {
            $dropColumns = [
                'status',
                'google_form_url',
                'poster_path',
                'attachment_path',
                'target_department',
                'target_course',
                'target_year_level',
                'checkin_start_at',
                'checkin_end_at',
            ];

            foreach ($dropColumns as $column) {
                if (Schema::hasColumn('events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
