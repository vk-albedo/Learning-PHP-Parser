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

    public static function bind($key, $value)
    {
        static::$registry[$key] = $value;
    }

    public static function get($key)
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

    public function getXpathFromPage($url)
    {
        $client = new Client();
        $request = $client->get($url);
        $content = $request->getBody()->getContents();

        $doc = new DOMDocument();
        @$doc->loadHTML($content);

        return new DOMXPath($doc);
    }

    public function addSetToRedis($set, $key)
    {
        $host = $this->get('config')['host'];
        $redis = $this->get('redis');

        $values = [];
        foreach ($set as $item) {
            $values[] = $host . $item->textContent;
        }

        $redis->sadd($key, $values);
    }

    public function pushToDb($question, $answers)
    {
        $database = $this->get('config')['database'];

        $pdo = $this->makeConnection($database);
        $connection = new QueryBuilder($pdo);

        $connection->addElement($question, $answers);

        $connection = null;
    }

    public function forkSet($function, $set_name, $parser)
    {
        $redis = $this->get('redis');

//        $n = 4;
//        while($n--){
//            echo $n."\n";
        while ($redis->smembers($set_name)) {
            $pid = pcntl_fork();

            if ($pid == -1) {
                exit("Error forking...\n");
            } elseif ($pid == 0) {
                $parser->$function($this);
                exit();
            }
        }
        while (pcntl_waitpid(0, $status) != -1);
    }
}
