<?php

namespace Database\Seeders;

use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(SettingsService $settings): void
    {
        $settings->setMany([
            'platform_name' => 'Wavixa Academy',
            'logo' => null,
            'favicon' => null,
            'whatsapp_number' => '',
            'whatsapp_message_template' => 'مرحبًا، أريد التسجيل في كورس: {course_name}',
            'support_email' => 'academy@wavixa.net',
            'timezone' => 'Africa/Cairo',
            'currency' => 'EGP',
            'default_language' => 'ar',
            'social_links' => [],
            'footer_about' => 'منصة تعليمية عربية تركّز على التعلم العملي وبناء مشاريع حقيقية.',
        ], 'general');

        $settings->setMany([
            'max_devices' => 2,
            'max_concurrent_sessions' => 1,
            'concurrent_policy' => 'terminate_oldest',
            'completion_threshold' => 0.9,
            'watermark_enabled' => true,
        ], 'security');
    }
}
