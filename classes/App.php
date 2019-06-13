<?php


namespace App;

use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;
use Logging\Logging;
use Redis\Redis;
use Scripts\ParseLetters;

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
        $this->logger->log(
            'INFO',
            'Crawler is running...',
            __FILE__
        );

        echo 'Crawler is running...';

        define('MAX_FORK', self::get('config')['MAX_FORK']);

        $pid_array = [];

        while (true) {
            echo sizeof($pid_array) . PHP_EOL;

            if (MAX_FORK <= sizeof($pid_array)) {
                continue;
            }

            if (!$this->redis->client->smembers('links')) {
                if (!sizeof($pid_array)) {
                    $this->logger->log(
                        'INFO',
                        'Crawler has finished',
                        __FILE__
                    );

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
                echo 'I`m parent' .PHP_EOL;

                // parent
                $pid_array[] = $pid;
                pcntl_wait($status);
                unset($pid_array[$pid]);
            } else {
                echo 'I`m child' .PHP_EOL;

                // child
                $this->redis->reconnect();

                $object = json_decode(trim($this->redis->client->spop('links')));

                $url = $object->{'url'};
                $class = $object->{'class'};

                echo $class . PHP_EOL;

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
