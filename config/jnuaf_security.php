<?php



return [

    // Mettre TWO_FACTOR_ADMIN_REQUIRED=false dans .env uniquement en cas de secours.

    'two_factor_admin_required' => env('TWO_FACTOR_ADMIN_REQUIRED', true),

    // Mettre TOTP_EMAIL_FALLBACK=false pour interdire le code par email aux comptes qui ont une application.
    'totp_email_fallback' => env('TOTP_EMAIL_FALLBACK', true),

    // off | report-only | enforce (CSP des pages d'administration)
    'csp_mode' => env('CSP_MODE', 'report-only'),

    // Deconnexion automatique des admins apres inactivite (minutes)
    'admin_idle_minutes' => env('ADMIN_IDLE_MINUTES', 30),

    // Application d'authentification obligatoire pour les comptes admin
    'admin_totp_required' => env('ADMIN_TOTP_REQUIRED', true),

    // Un admin qui a une application ne peut plus recevoir de code par email
    'admin_email_fallback' => env('ADMIN_EMAIL_FALLBACK', false),

];

