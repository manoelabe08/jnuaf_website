<?php
return [
    'per_minute' => (int) env('NEWSLETTER_PER_MINUTE', 5),
    'per_hour' => (int) env('NEWSLETTER_PER_HOUR', 100),
    'per_day' => (int) env('NEWSLETTER_PER_DAY', 900),
    'batch_seconds' => 25,
    'smtp_timeout' => 10,
    'max_attachment_kb' => 5120,
    'max_total_bytes' => 10485760,
    'max_attachments' => 5,
    'web_days' => 90,
    'campaign_limit' => 5000,
];
