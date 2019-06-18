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
    protected $isChild = false;

    public function __construct()
    {
        $this->logger = new Logging();
        $this->redis = new Redis();
    }

    public function __destruct()
    {
        echo getmypid();

        echo ' is Child ';
        var_dump($this->isChild);
        if ($this->isChild == false) {
            echo 'destructor ';

            var_dump(self::$pid_array);


            $objects = [];
            foreach (self::$pid_array as $pid => $object) {
                $objects[] = $object;

                $url = trim($object->{'url'});

                $this->logger->log(
                    'INFO',
                    "Return url to list after closing: {$url}",
                    __FILE__
                );

                exec("kill -9 {$pid}");
            }
            if ($objects) {
                $this->addSetToRedis($objects);
            }
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

            foreach (self::$pid_array as $pid => $object) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);

                // If the process has already exited
                if ($res == -1 || $res > 0) {
                    echo 'Unset ' . $pid . PHP_EOL;
                    unset(self::$pid_array[$pid]);
                }

                sleep(1);
            }

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

                    $this->isChild = true;
                    $this->addSetToRedis([$object,]);

                    exit();
                case 0:
                    // child
                    $this->isChild = true;

                    $url = trim($object->{'url'});
                    $class = 'Scripts\\' . trim($object->{'class'});

                    $parser = new $class();
                    $parser->parse($url);

                    exit();
                default:
                    // parent

                    var_dump($pid);

                    self::$pid_array["{$pid}"] = $object;

                    var_dump(self::$pid_array);
            }
        }
    }

    public function getXpathFromPage($url)
    {
        do {
            $proxy = Proxy::getProxy();

            $client = new Client(
                [
                    'request.options' => [
                        'proxy' => $proxy,
                    ],
                ]
            );
            $request = $client->get($url);
        } while ($request->getStatusCode() != 200);

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
