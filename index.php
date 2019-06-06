<?php

require 'vendor/autoload.php';

use App\App;
use Database\Connection;
use Database\QueryBuilder;
use Scripts\Parse;


App::bind('config', require 'config.php');

try {
    $database = App::get('config')['database'];

    $connection = new QueryBuilder(
        Connection::make(
            $database,
            true)
    );

    $connection->createDatabase($database);
    $connection->createTables($database);

} catch (Exception $exception) {
    echo $exception->getMessage();
}


Parse::parse();
