<?php

return [
    // Chemins absolus : les PDF doivent être HORS de la racine web.
    'private_root' => env('PARTNER_PRIVATE_ROOT'),
    'web_root' => env('PARTNER_WEB_ROOT'),
    'admin_emails' => array_values(array_filter(array_map('trim', explode(',', (string) env('PARTNER_ADMIN_EMAILS', ''))))),
    'contact_email' => env('PARTNER_CONTACT_EMAIL'),
    'code_seconds' => 300,
    'second_delay' => 90,
    'third_delay' => 150,
    'idle_seconds' => 1800,
    'session_seconds' => 7200,
    'max_upload_kb' => 20480,
];
