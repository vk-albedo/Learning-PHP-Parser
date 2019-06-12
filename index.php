<?php

require 'vendor/autoload.php';

use App\App;
use Scripts\Parse;

App::bind('config', require 'config.php');

Predis\Autoloader::register();
App::bind('redis', new Predis\Client('tcp://127.0.0.1:6379'."?read_write_timeout=0"));


// Mode #1: collecting links and data
// Mode #2: data collection
// Use mode #2 when you need to continue saving data after restarting the parser
$mode = 1;

$parser = new Parse();
$parser->parse($mode);
