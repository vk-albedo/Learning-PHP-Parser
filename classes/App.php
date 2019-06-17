<?php


namespace App;

use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;
use Logging\Logging;
use Proxy\Proxy;
use Redis\Redis;

class App
{
    protected static $registry = [];
    protected $logger;
    protected $redis;
    protected static $pid_array = [];

    public function __construct()
    {
        $this->logger = new Logging();
        $this->redis = new Redis();
    }

    public function __destruct()
    {
        $objects = [];
        foreach (self::$pid_array as $pid => $object) {
            $objects[] = $object;

            $url = trim($object->{'url'});

            $this->logger->log(
                'INFO',
                "Return url to list: {$url}",
                __FILE__
            );

            exec("kill -9 {$pid}");
        }
        if ($objects) {
            $this->addSetToRedis($objects);
        }
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
        while (true) {
            var_dump(self::$pid_array);

            if (MAX_FORK <= sizeof(self::$pid_array)) {
                continue;
            }

            if (!$this->redis->client->smembers('links')) {
                if (!sizeof(self::$pid_array)) {
                    break;
                }
                continue;
            }

            $object = json_decode($this->redis->client->spop('links'));

            $pid = pcntl_fork();
            $this->redis->reconnect();

            switch ($pid) {
                case -1:
                    $this->logger->log(
                        'ERROR',
                        'Could not fork',
                        __FILE__
                    );

                    exit();
                case 0:
                    // child
                    $url = trim($object->{'url'});
                    $class = 'Scripts\\' . trim($object->{'class'});

                    $parser = new $class();
                    $parser->parse($url);

                    sleep(60);

                    exit();
                default:
                    // parent

                    var_dump($pid);

                    self::$pid_array["{$pid}"] = $object;

                    var_dump(self::$pid_array);

                    pcntl_wait($status);

                    unset(self::$pid_array[$pid]);
            }
        }
    }

    public function getXpathFromPage($url)
    {
        $proxy = Proxy::getProxy();

        $client = new Client(
            [
            'request.options' => [
                'proxy' => $proxy,
                ],
            ]
        );
        $request = $client->get($url);
        $content = $request->getBody()->getContents();

        $doc = new DOMDocument();
        @$doc->loadHTML($content);

        return new DOMXPath($doc);
    }

    public function encodeToJSON($urls, $class)
    {
        $values = [];

        foreach ($urls as $url) {
            $values[] = json_encode(
                array(
                    'url' => $url,
                    'class' => $class
                )
            );
        }

        return $values;
    }

    public function addSetToRedis($objects)
    {
        $this->redis->client->sadd('links', $objects);
    }
}
