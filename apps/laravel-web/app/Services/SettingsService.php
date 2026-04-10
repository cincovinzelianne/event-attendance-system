<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->allKeyValue();

        return $settings[$key] ?? $default;
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::query()->updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => is_array($value) ? $value : ['value' => $value],
                'group_name' => $group,
            ]
        );

        Cache::forget('system.settings');
    }

    public function allGrouped(): array
    {
        return Setting::query()
            ->orderBy('group_name')
            ->orderBy('setting_key')
            ->get()
            ->groupBy('group_name')
            ->map(fn ($rows) => $rows->keyBy('setting_key')->map(fn ($row) => $row->setting_value)->all())
            ->all();
    }

    private function allKeyValue(): array
    {
        return Cache::remember('system.settings', 300, function (): array {
            return Setting::query()
                ->get()
                ->mapWithKeys(fn (Setting $setting): array => [
                    $setting->setting_key => $setting->setting_value['value'] ?? $setting->setting_value,
                ])->all();
        });
    }
}
