<?php

require 'vendor/autoload.php';

use App\App;
use Database\QueryBuilder;
use Scripts\Parse;

$app = new App();
$app->bind('config', require 'config.php');

Predis\Autoloader::register();
$app->bind('redis', new Predis\Client('tcp://127.0.0.1:6379'."?read_write_timeout=0"));

$database = $app->get('config')['database'];

$connection = new QueryBuilder(
    $app->make_connection(
        $database,
        true)
);

$connection->createDatabase($database);
$connection->createTables($database);
$connection = null;


// Mode #1: collecting links and data
// Mode #2: data collection
// Use mode #2 when you need to continue saving data after restarting the parser
$mode = 1;

$parser = new Parse();
$parser->parse($mode);
