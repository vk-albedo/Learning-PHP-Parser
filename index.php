

// Database Structure
CREATE TABLE 'webpage_details' (
'link' text NOT NULL,
 'title' text NOT NULL,
 'description' text NOT NULL,
 'internal_link' text NOT NULL,
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1

<?php

require 'vendor/autoload.php';

$site_url = "https://www.kreuzwort-raetsel.net/uebersicht.html";

$client = new GuzzleHttp\Client();

$result = $client->get($site_url);

$content = $result->getBody()->getContents();



echo $content;