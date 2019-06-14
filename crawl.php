<?php

require 'vendor/autoload.php';

use App\App;
use Logging\Logging;

App::bind('config', require 'config.php');

Predis\Autoloader::register();

$logger = new Logging();
$logger->log(
    'INFO',
    'Crawler is running...',
    __FILE__
);

$app = new App();
$app->run();

$logger->log(
    'INFO',
    'Crawler has finished',
    __FILE__
);
