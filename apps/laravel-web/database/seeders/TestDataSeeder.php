<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    /**
     * Seed realistic demo data for manual QA and local testing.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'qa-admin@event-attendance.test'],
            [
                'name' => 'QA Administrator',
                'role' => 'admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $students = collect();

        for ($i = 1; $i <= 15; $i++) {
            $student = User::query()->updateOrCreate(
                ['email' => sprintf('student%02d@event-attendance.test', $i)],
                [
                    'name' => sprintf('Test Student %02d', $i),
                    'role' => 'student',
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            );

            $students->push($student);

            DB::table('student_profiles')->updateOrInsert(
                ['user_id' => $student->id],
                [
                    'student_id' => sprintf('2026-%04d', $i + 100),
                    'first_name' => 'Test',
                    'last_name' => sprintf('Student%02d', $i),
                    'phone' => sprintf('0917000%04d', $i),
                    'course_id' => null,
                    'year_level' => (string) ((($i - 1) % 4) + 1),
                    'department' => $i % 2 === 0 ? 'College of Computer Studies' : 'College of Engineering',
                    'section' => 'SEC-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'profile_completed' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $events = collect([
            [
                'title' => 'Tech Talk: Modern Web Stack',
                'description' => 'Deep dive session for students and faculty.',
                'starts_at' => now()->addDays(1)->setTime(14, 0),
                'ends_at' => now()->addDays(1)->setTime(16, 30),
                'location' => 'Innovation Hall',
                'attendance_locked' => false,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Department Assembly',
                'description' => 'Monthly assembly for major announcements.',
                'starts_at' => now()->addDays(2)->setTime(10, 0),
                'ends_at' => now()->addDays(2)->setTime(12, 0),
                'location' => 'Main Auditorium',
                'attendance_locked' => false,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Career Fair 2026',
                'description' => 'Industry partners and job opportunities for graduating students.',
                'starts_at' => now()->addDays(7)->setTime(9, 0),
                'ends_at' => now()->addDays(7)->setTime(17, 0),
                'location' => 'University Grounds',
                'attendance_locked' => false,
                'created_by' => $admin->id,
            ],
        ])->map(function (array $eventData): Event {
            return Event::query()->updateOrCreate(['title' => $eventData['title']], $eventData);
        });

        $this->seedAttendances($events, $students);
        $this->seedNotifications($admin->id, $students);
        $this->seedActivityLogs($admin->id, $students);
    }

    private function seedAttendances(Collection $events, Collection $students): void
    {
        foreach ($events as $eventIndex => $event) {
            foreach ($students as $studentIndex => $student) {
                // Keep a realistic attendance spread across events.
                if ((($studentIndex + $eventIndex) % 4) !== 0) {
                    Attendance::query()->updateOrCreate(
                        [
                            'event_id' => $event->id,
                            'user_id' => $student->id,
                        ],
                        [
                            'checked_in_at' => $event->starts_at->copy()->addMinutes(($studentIndex % 6) * 5),
                            'status' => 'present',
                            'scan_source' => $studentIndex % 2 === 0 ? 'web' : 'qr-scanner',
                        ]
                    );
                }
            }
        }
    }

    private function seedNotifications(int $adminId, Collection $students): void
    {
        foreach ($students as $student) {
            Notification::query()->updateOrCreate(
                [
                    'user_id' => $student->id,
                    'title' => 'Upcoming Event Reminder',
                ],
                [
                    'message' => 'You have upcoming events this week. Please keep your QR code ready for scanning.',
                    'type' => 'reminder',
                    'status' => 'unread',
                    'read_at' => null,
                    'meta' => ['seed' => 'test', 'priority' => 'normal'],
                    'created_by' => $adminId,
                ]
            );
        }
    }

    private function seedActivityLogs(int $adminId, Collection $students): void
    {
        DB::table('activity_logs')->updateOrInsert(
            ['user_id' => $adminId, 'action_type' => 'seed.test_data'],
            [
                'action_data' => json_encode([
                    'students_seeded' => $students->count(),
                    'generated_by' => 'TestDataSeeder',
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
