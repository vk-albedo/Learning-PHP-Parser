<?php

namespace App;

use Database\Connection;
use Database\QueryBuilder;
use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;

class App
{
    protected static $registry = [];

    public static function bind($key, $value)
    {
        static::$registry[$key] = $value;
    }

    public static function get($key)
    {
        if (!array_key_exists($key, static::$registry)) {
            throw new Exception("No {$key} is bound in the container.\n");
        }

        return static::$registry[$key];
    }

    public static function get_xpath_from_page($url)
    {
        $client = new Client();
        $request = $client->get($url);
        $content = $request->getBody()->getContents();

        $doc = new DOMDocument();
        @$doc->loadHTML($content);

        return new DOMXPath($doc);
    }

    public static function add_set_to_redis($set, $key)
    {
        try {
            $host = self::get('config')['host'];
            $redis = self::get('redis');

            $values = [];
            foreach ($set as $item) {
                $values[] = $host . $item->textContent;
            }

            $redis->sadd($key, $values);

        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public static function push_to_db($question, $answers)
    {
        try {
            $database = self::get('config')['database'];

            $connection = new QueryBuilder(
                Connection::make($database)
            );

            $connection->addElement($database, $question, $answers);

            $connection = null;

        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }
}