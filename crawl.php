<?php

require 'vendor/autoload.php';

use App\App;

App::bind('config', require 'config.php');

Predis\Autoloader::register();

$app = new App();
$app->run();
