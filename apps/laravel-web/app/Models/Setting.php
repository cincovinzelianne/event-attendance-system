<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_value',
        'group_name',
    ];

    protected function casts(): array
    {
        return [
            'setting_value' => 'array',
        ];
    }
}
