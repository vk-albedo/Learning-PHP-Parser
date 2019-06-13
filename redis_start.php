<?php

require 'vendor/autoload.php';


use App\App;
use Logging\Logging;
use Redis\Redis;

App::bind('config', require 'config.php');

$logger = new Logging();

$start_url = App::get('config')['start_url'];
$class = 'ParseLetters';

$data = json_encode(
    array(
        'url' => $start_url,
        'class' => $class
        )
);

$redis = new Redis();
$redis->connect();

try {
    $redis->client->sadd('links', $data);
} catch (Exception $exception) {
    $this->logger->log(
        'ERROR',
        "Exception: {$exception->getMessage()}",
        __FILE__
    );
}

$logger->log(
    'INFO',
    'Added start url to Redis',
    __FILE__
);
