<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'description',
    'starts_at',
    'checkin_start_at',
    'checkin_end_at',
    'ends_at',
    'location',
    'google_form_url',
    'poster_path',
    'attachment_path',
    'target_department',
    'target_course',
    'target_year_level',
    'status',
    'attendance_locked',
    'created_by',
])]
class Event extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'checkin_start_at' => 'datetime',
            'checkin_end_at' => 'datetime',
            'ends_at' => 'datetime',
            'attendance_locked' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
