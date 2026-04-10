<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:verify-attendance-integrity')]
#[Description('Verify there are no duplicate attendance rows per event and user')]
class VerifyAttendanceIntegrity extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $duplicates = DB::table('attendances')
            ->select('event_id', 'user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('event_id', 'user_id')
            ->having('total', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('Attendance integrity check passed. No duplicate records found.');

            return self::SUCCESS;
        }

        $this->error('Attendance integrity check failed. Duplicate records detected:');

        foreach ($duplicates as $row) {
            $this->line("event_id={$row->event_id}, user_id={$row->user_id}, duplicates={$row->total}");
        }

        return self::FAILURE;
    }
}
