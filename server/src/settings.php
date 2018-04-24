<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../../dist/client/',
//            'template_path' => __DIR__ . '/../../client/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // Database settings
        'database' => [
            'dsn' => 'mysql:dbname=ccc;host=localhost',
            'username' => 'root',
            'password' => '',
        ],

        // archive path
        'archivePath' => __DIR__ . '/../../uploads/',

        // temporary path (or use system temp path
        'tempPath' => __DIR__ . '/../../temp/',

        'LDAP_HOST' => "ldap.example.com"
    ],
];
