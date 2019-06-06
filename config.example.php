<?php

return [
    'database' => [
        'name' => 'your_dbname',
        'username' => 'your_name',
        'password' => 'your_pass',
        'connection' => 'mysql:host=localhost',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
        ],
    ],
    'site_url' => 'https://www.kreuzwort-raetsel.net/uebersicht.html',
];