<?php


namespace Proxy;

use GuzzleHttp\Client;
use Logging\Logging;

class Proxy
{
    protected static $storage = [];
    protected $logger;

    public function __construct()
    {
        $this->logger = new Logging();
        $this->updateStorage();
    }

    public static function getProxy()
    {
        $proxy = self::$storage[0];

        if (!self::$storage) {
            (new Proxy())->updateStorage();
        }

        return $proxy;
    }

    protected function updateStorage()
    {
        $client = new Client();
        $request = $client->get("https://api.best-proxies.ru/proxylist.txt?key=ba94f47b89e7ce92facb36a36775e433&type=https&google=1&level=1&limit=100&includeType&country=us");
        $content = $request->getBody()->getContents();
        $result = explode("\n", $content);

        foreach ($result as $proxy) {
            self::$storage[] = $proxy;
        }

        $num_proxy = sizeof($result);

        $this->logger->log(
            'INFO',
            "Get {$num_proxy} proxies\n",
            __FILE__
        );
    }
}
