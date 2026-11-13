<?php

return [
    'imap' => [
        'host' => env(
            'ADMIN_MAIL_IMAP_HOST',
            'mail65.lwspanel.com'
        ),

        'port' => (int) env(
            'ADMIN_MAIL_IMAP_PORT',
            993
        ),

        'username' => env(
            'ADMIN_MAIL_IMAP_USERNAME',
            'team@jnuaf.org'
        ),

        'password' => env(
            'ADMIN_MAIL_IMAP_PASSWORD'
        ),

        'timeout' => 10,

        'verify_certificate' => true,
    ],
];