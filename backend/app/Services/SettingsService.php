<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_PREFIX = 'settings:';

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$key, function () use ($key, $default) {
            $row = Setting::where('key', $key)->first();

            return $row ? $row->value['v'] ?? $default : $default;
        });
    }

    public function set(string $key, mixed $value, string $group = 'general'): Setting
    {
        $row = Setting::updateOrCreate(
            ['key' => $key],
            ['value' => ['v' => $value], 'group' => $group]
        );
        Cache::forget(self::CACHE_PREFIX.$key);

        return $row;
    }

    public function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    public function group(string $group): array
    {
        return Setting::where('group', $group)->get()
            ->mapWithKeys(fn (Setting $s) => [$s->key => $s->value['v'] ?? null])
            ->all();
    }

    public function publicSettings(): array
    {
        return [
            'platform_name' => $this->get('platform_name', 'Wavixa Academy'),
            'logo' => $this->get('logo'),
            'favicon' => $this->get('favicon'),
            'whatsapp_number' => $this->get('whatsapp_number'),
            'whatsapp_message_template' => $this->get('whatsapp_message_template', 'مرحبًا، أريد التسجيل في كورس: {course_name}'),
            'support_email' => $this->get('support_email'),
            'default_language' => $this->get('default_language', 'ar'),
            'social_links' => $this->get('social_links', []),
            'footer_about' => $this->get('footer_about'),
        ];
    }
}
