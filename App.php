<?php

namespace App;

use Database\QueryBuilder;
use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;
use PDO;
use PDOException;

class App
{
    protected static $registry = [];

    public function bind($key, $value)
    {
        static::$registry[$key] = $value;
    }

    public function get($key)
    {
        try {
            if (!array_key_exists($key, static::$registry)) {
                throw new Exception("No {$key} is bound in the container.\n");
            }

            return static::$registry[$key];

        } catch (Exception $exception) {
            echo $exception->getMessage();
        }

        return [];
    }

    public function make_connection($config, $first = false)
    {
        try {
            $dbname = (!$first) ? ';dbname=' . $config['name'] : '';

            return new PDO(
                $config['connection'] . $dbname,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $exception) {
            die($exception->getMessage());
        }
    }

    public function get_xpath_from_page($url)
    {
        $client = new Client();
        $request = $client->get($url);
        $content = $request->getBody()->getContents();

        $doc = new DOMDocument();
        @$doc->loadHTML($content);

        return new DOMXPath($doc);
    }

    public function add_set_to_redis($set, $key)
    {
        $host = self::get('config')['host'];
        $redis = self::get('redis');

        $values = [];
        foreach ($set as $item) {
            $values[] = $host . $item->textContent;
        }

        $redis->sadd($key, $values);
    }

    public function push_to_db($question, $answers)
    {
        $database = self::get('config')['database'];

        $pdo = $this->make_connection($database);
        $connection = new QueryBuilder($pdo);

        $connection->addElement($question, $answers);

        $connection = null;
    }
}
