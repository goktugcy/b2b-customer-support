<?php

return [
    'attachments' => [
        'max_kilobytes' => (int) env('SUPPORT_ATTACHMENT_MAX_KB', 20480),
        'allowed_mimes' => array_filter(array_map('trim', explode(',', (string) env(
            'SUPPORT_ATTACHMENT_ALLOWED_MIMES',
            'text/plain,text/csv,application/pdf,image/png,image/jpeg,image/gif,application/zip,application/json,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        )))),
        'allowed_extensions' => array_filter(array_map('trim', explode(',', (string) env(
            'SUPPORT_ATTACHMENT_ALLOWED_EXTENSIONS',
            'txt,csv,pdf,png,jpg,jpeg,gif,zip,json,docx,xlsx'
        )))),
    ],

    'sla' => [
        'defaults' => [
            'low' => ['first_response_minutes' => 480, 'resolution_minutes' => 4320],
            'normal' => ['first_response_minutes' => 240, 'resolution_minutes' => 1440],
            'high' => ['first_response_minutes' => 60, 'resolution_minutes' => 480],
            'urgent' => ['first_response_minutes' => 15, 'resolution_minutes' => 120],
        ],
    ],

    'inbound_email' => [
        'secret' => env('INBOUND_EMAIL_SECRET'),
        'default_company' => env('INBOUND_EMAIL_DEFAULT_COMPANY'),
    ],

    'clamav' => [
        'enabled' => (bool) env('CLAMAV_ENABLED', false),
        'host' => env('CLAMAV_HOST', '127.0.0.1'),
        'port' => (int) env('CLAMAV_PORT', 3310),
    ],
];
