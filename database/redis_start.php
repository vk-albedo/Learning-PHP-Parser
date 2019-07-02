<?php

require '../vendor/autoload.php';


use App\App;
use Logging\Logging;
use Database\Redis;

App::bind('config', require '../config.php');

$logger = new Logging();

$start_url = App::get('config')['start_url'];
$class = 'ParseLetters';

//$start_url = 'https://www.kreuzwort-raetsel.net/umschreibung_fabelwesen.html';
//$class = 'ParseAnswers';

$data = json_encode(
    array(
        'url' => $start_url,
        'class' => $class
        )
);

$redis = new Redis();

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
