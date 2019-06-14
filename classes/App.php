<?php


namespace App;

use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;
use Logging\Logging;
use Redis\Redis;

class App
{
    protected static $registry = [];
    protected $logger;
    protected $redis;

    public function __construct()
    {
        $this->logger = new Logging();
        $this->redis = new Redis();
        $this->redis->connect();
    }

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

    public function run()
    {
        define('MAX_FORK', self::get('config')['MAX_FORK']);

        $pid_array = [];

        while (true) {
            if (MAX_FORK <= sizeof($pid_array)) {
                continue;
            }

            if (!$this->redis->client->smembers('links')) {
                if (!sizeof($pid_array)) {
                    break;
                }
                continue;
            }

            $pid = pcntl_fork();
            if ($pid == -1) {
                $this->logger->log(
                    'ERROR',
                    'Could not fork',
                    __FILE__
                );
                exit();
            } elseif ($pid) {
                // parent
                $pid_array[] = $pid;

                pcntl_wait($status);

                unset($pid_array[array_search($pid, $pid_array)]);
            } else {
                // child
                $this->redis->reconnect();

                $object = json_decode($this->redis->client->spop('links'));

                $url = trim($object->{'url'});
                $class = 'Scripts\\' .trim($object->{'class'});

                $parser = new $class();
                $parser->parse($url);
            }
        }
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

    public function addSetToRedis($urls, $class)
    {
        $host = self::get('config')['host'];
        $values = [];

        foreach ($urls as $url) {
            $url = $host . $url->textContent;

            $values[] = json_encode(
                array(
                    'url' => $url,
                    'class' => $class
                )
            );
        }

        $this->redis->client->sadd('links', $values);
    }
}
