<?php

declare(strict_types=1);

return [
    'transport' => env('MAIL_TRANSPORT', 'log'),
    'host' => env('MAIL_HOST', '127.0.0.1'),
    'port' => (int) env('MAIL_PORT', '1025'),
    'encryption' => env('MAIL_ENCRYPTION', ''),
    'username' => env('MAIL_USER', ''),
    'password' => env('MAIL_PASS', ''),
    'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@sctech.ma'),
    'from_name' => env('MAIL_FROM_NAME', 'SCTECH'),
    'to_address' => env('MAIL_TO_ADDRESS', 'contact@sctech.ma'),
    'required' => filter_var(env('FORM_MAIL_REQUIRED', 'true'), FILTER_VALIDATE_BOOL),
];
