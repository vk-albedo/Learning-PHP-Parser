<?php

require 'vendor/autoload.php';

use App\App;
use Database\QueryBuilder;
use Scripts\Parse;

$app = new App();
$app->bind('config', require 'config.php');

Predis\Autoloader::register();
$app->bind('redis', new Predis\Client());

$database = $app->get('config')['database'];

$connection = new QueryBuilder(
    $app->make_connection(
        $database,
        true)
);

$connection->createDatabase($database);
$connection->createTables($database);
$connection = null;

$parser = new Parse();
$parser->parse();
