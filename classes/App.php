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
            (new App())->logger->log(
                'ERROR',
                $exception->getMessage(),
                __FILE__
            );
        }

        return [];
    }

    public function run()
    {
        while (true) {
            foreach (self::$pid_array as $pid => $object) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);

                // If the process has already exited
                if ($res == -1 || $res > 0) {
                    $this->redis->client->srem(
                        'links',
                        json_encode(
                            array(
                                'url' => (self::$pid_array["{$pid}"])->url,
                                'class' => (self::$pid_array["{$pid}"])->class
                            )
                        )
                    );
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

            $object = json_decode($this->redis->client->srandmember('links'));

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

                    exit('Could not fork');
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
                    self::$pid_array["{$pid}"] = $object;
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
