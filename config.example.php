<?php

// Your user should be able to create databases
// Database name 'crosswords' must be unique for this parser

return [
    'database' => [
        'name' => 'crosswords',
        'username' => 'your_name',
        'password' => 'your_pass',
        'connection' => 'mysql:host=localhost',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
        ],
    ],
    'site_url' => 'https://www.kreuzwort-raetsel.net/uebersicht.html',
    'host' => 'https://www.kreuzwort-raetsel.net/',
    'log_filename' => 'log',
];
