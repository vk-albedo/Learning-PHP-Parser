<?php

namespace Scripts;

use App\App;
use Exception;
use GuzzleHttp\Client;

class Parse
{
    public static function parse()
    {
        try {
            $site_url = App::get('config')['site_url'];

            $client = new Client();
            $result = $client->get($site_url);
            $content = $result->getBody()->getContents();

            echo $content;


        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }
}
