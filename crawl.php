<?php

require 'vendor/autoload.php';

use App\App;
use Logging\Logging;
use Proxy\Proxy;

App::bind('config', require 'config.php');

define('MAX_FORK', App::get('config')['MAX_FORK']);

Predis\Autoloader::register();
new Proxy();

$logger = new Logging();
$logger->log(
    'INFO',
    PHP_EOL.'START CRAWLER.'.PHP_EOL.'Crawler is running...',
    __FILE__
);

$app = new App();
$app->run();

$logger->log(
    'INFO',
    'Crawler has finished',
    __FILE__
);
