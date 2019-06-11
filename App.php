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
        $host = $this->get('config')['host'];
        $redis = $this->get('redis');

        $values = [];
        foreach ($set as $item) {
            $values[] = $host . $item->textContent;
        }

        $redis->sadd($key, $values);
    }

    public function push_to_db($question, $answers)
    {
        $database = $this->get('config')['database'];

        $pdo = $this->make_connection($database);
        $connection = new QueryBuilder($pdo);

        $connection->addElement($question, $answers);

        $connection = null;
    }

    public function fork_set($function, $set_name, $parser)
    {
        $redis = $this->get('redis');

//        $n = 4;
//        while($n--){
//            echo $n."\n";
        while($redis->smembers($set_name)){
            $pid = pcntl_fork();

            if ($pid == -1) {
                exit("Error forking...\n");
            }
            else if ($pid == 0) {
                $parser->$function($this);
                exit();
            }
        }
        while(pcntl_waitpid(0, $status) != -1);
    }
}
