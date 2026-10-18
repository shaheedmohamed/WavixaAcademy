<?php

return [
    'video_session_ttl' => (int) env('VIDEO_SESSION_TTL_MINUTES', 15),
    'heartbeat_interval' => (int) env('VIDEO_HEARTBEAT_INTERVAL_SECONDS', 20),
    'completion_threshold' => (float) env('VIDEO_COMPLETION_THRESHOLD', 0.9),
    'max_devices' => (int) env('VIDEO_MAX_DEVICES', 2),
    'max_concurrent_sessions' => (int) env('VIDEO_MAX_CONCURRENT_SESSIONS', 1),
    'concurrent_policy' => env('VIDEO_CONCURRENT_POLICY', 'terminate_oldest'),
    'watermark_enabled' => (bool) env('VIDEO_WATERMARK_ENABLED', true),
    'watermark_interval' => (int) env('VIDEO_WATERMARK_INTERVAL_SECONDS', 45),
    'enrollment_default_expiry_days' => env('ENROLLMENT_DEFAULT_EXPIRY_DAYS'),
];
