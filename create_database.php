<?php

require 'vendor/autoload.php';

use App\App;
use Database\Database;
use Logging\Logging;



App::bind('config', require 'config.php');
$database = App::get('config')['database'];
$logger = new Logging();
$connection = new Database();
$connection->connect(
    $database,
    true
);

$sql = file_get_contents('db_structure.sql');

if ($sql) {
    $logger->log(
        'INFO',
        'Open the file: db_structure.sql',
        __FILE__
    );

    $connection->execute($sql);
} else {
    $logger->log(
        'ERROR',
        'Cannot open the file: db_structure.sql',
        __FILE__
    );
}

unset($connection);
