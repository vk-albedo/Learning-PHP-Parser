<?php

require 'vendor/autoload.php';

use App\App;
use Database\Connection;
use Database\QueryBuilder;
use Scripts\Parse;


App::bind('config', require 'config.php');

Predis\Autoloader::register();
try {
    App::bind('redis', new Predis\Client());
}
catch (Exception $exception) {
    die($exception->getMessage());
}

try {
    $database = App::get('config')['database'];

    $connection = new QueryBuilder(
        Connection::make(
            $database,
            true)
    );

    $connection->createDatabase($database);
    $connection->createTables($database);

    $connection = null;

} catch (Exception $exception) {
    echo $exception->getMessage();
}

Parse::parse();
