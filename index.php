<?php

require 'vendor/autoload.php';

$site_url = "https://www.kreuzwort-raetsel.net/uebersicht.html";

$client = new GuzzleHttp\Client();

$result = $client->get($site_url);

$content = $result->getBody()->getContents();



echo $content;