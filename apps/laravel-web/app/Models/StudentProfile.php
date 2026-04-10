<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'student_id',
        'first_name',
        'last_name',
        'phone',
        'course_id',
        'year_level',
        'department',
        'section',
        'qr_code_token',
        'qr_code_path',
        'profile_completed',
    ];

    protected function casts(): array
    {
        return [
            'profile_completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
