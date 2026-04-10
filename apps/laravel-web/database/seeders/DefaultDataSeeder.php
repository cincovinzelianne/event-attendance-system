<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultDataSeeder extends Seeder
{
    /**
     * Seed baseline data required for a usable fresh install.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@event-attendance.test'],
            [
                'name' => 'System Administrator',
                'role' => 'admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $student = User::query()->updateOrCreate(
            ['email' => 'student@event-attendance.test'],
            [
                'name' => 'Default Student',
                'role' => 'student',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        DB::table('courses')->upsert(
            [
                [
                    'department' => 'College of Computer Studies',
                    'course_name' => 'BS Information Technology',
                    'major' => null,
                    'year_levels' => json_encode(['1', '2', '3', '4']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'department' => 'College of Engineering',
                    'course_name' => 'BS Computer Engineering',
                    'major' => null,
                    'year_levels' => json_encode(['1', '2', '3', '4', '5']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            ['department', 'course_name', 'major'],
            ['year_levels', 'is_active', 'updated_at']
        );

        $courseId = DB::table('courses')
            ->where('department', 'College of Computer Studies')
            ->where('course_name', 'BS Information Technology')
            ->value('id');

        DB::table('student_profiles')->updateOrInsert(
            ['user_id' => $student->id],
            [
                'student_id' => '2026-0001',
                'first_name' => 'Default',
                'last_name' => 'Student',
                'phone' => '09171234567',
                'course_id' => $courseId,
                'year_level' => '3',
                'department' => 'College of Computer Studies',
                'section' => 'IT-3A',
                'profile_completed' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $event = Event::query()->updateOrCreate(
            ['title' => 'Campus Orientation'],
            [
                'description' => 'Default orientation event for system validation.',
                'starts_at' => now()->addDays(3)->setTime(9, 0),
                'ends_at' => now()->addDays(3)->setTime(11, 0),
                'location' => 'Main Auditorium',
                'attendance_locked' => false,
                'created_by' => $admin->id,
            ]
        );

        DB::table('event_departments')->updateOrInsert(
            ['event_id' => $event->id, 'department' => 'College of Computer Studies'],
            ['updated_at' => now(), 'created_at' => now()]
        );

        Notification::query()->updateOrCreate(
            [
                'user_id' => $student->id,
                'title' => 'Welcome to Event Attendance System',
            ],
            [
                'message' => 'Your account is ready. You can now view events and scan attendance QR codes.',
                'type' => 'system',
                'status' => 'unread',
                'meta' => ['seed' => 'default'],
                'created_by' => $admin->id,
            ]
        );
    }
}
