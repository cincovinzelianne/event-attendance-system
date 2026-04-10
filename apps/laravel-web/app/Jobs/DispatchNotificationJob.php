<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly array $payload,
        public readonly string $targetRole,
        public readonly int $createdBy,
        public readonly ?string $scheduledFor = null,
        public readonly ?string $targetDepartment = null,
        public readonly array $targetUserIds = [],
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $query = User::query()->select(['id']);

        if ($this->targetRole !== 'all') {
            $query->where('role', $this->targetRole);
        }

        if (! empty($this->targetUserIds)) {
            $query->whereIn('id', $this->targetUserIds);
        }

        if ($this->targetDepartment !== null && $this->targetDepartment !== '') {
            $query->whereHas('studentProfile', function ($profileQuery): void {
                $profileQuery->where('department', $this->targetDepartment);
            });
        }

        $users = $query->get();

        $now = now();
        $rows = $users->map(fn (User $user): array => [
            'user_id' => $user->id,
            'title' => $this->payload['title'],
            'message' => $this->payload['message'],
            'type' => $this->payload['type'] ?? 'general',
            'status' => 'unread',
            'meta' => json_encode([
                'target_role' => $this->targetRole,
                'target_department' => $this->targetDepartment,
                'target_user_ids' => $this->targetUserIds,
            ], JSON_THROW_ON_ERROR),
            'scheduled_for' => $this->scheduledFor,
            'created_by' => $this->createdBy,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (! empty($rows)) {
            Notification::query()->insert($rows);
        }
    }
}
