<?php

return [
    'author'         => 'Bison Digital',
    'author_url'     => 'https://gobison.digital',
    'name'           => 'Mailroom',
    'description'    => 'Transactional email delivery, logging, retry, and diagnostics for ExpressionEngine.',
    'version'        => '0.2.3',
    'namespace'      => 'BisonDigital\Mailroom',
    'settings_exist' => true,

    'requires' => [
        'php' => '8.1',
        'ee'  => '7.0.0',
    ],

    'mcp' => [
        'scan'    => ['Mcp'],
        'enabled' => true,
    ],

    'extensions' => [
        [
            'hook'     => 'email_send',
            'method'   => 'email_send',
            'priority' => 10,
            'enabled'  => true,
        ],
    ],
];
