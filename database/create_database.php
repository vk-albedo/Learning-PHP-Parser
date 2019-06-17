<?php

require '../vendor/autoload.php';

use App\App;
use Database\Database;
use Logging\Logging;

App::bind('config', require '../config.php');

$logger = new Logging();

$connection = new Database(true);

$sql = file_get_contents('../database/sql/db_structure.sql');

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

$logger->log(
    'INFO',
    'Database created',
    __FILE__
);

unset($connection);
