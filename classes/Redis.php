<?php


namespace Redis;

use Predis;

class Redis
{
    public $client;

    public function __construct()
    {
        $this->connect();
    }

    public function __destruct()
    {
        $this->client = null;
    }

    protected function connect()
    {
        $this->client = new Predis\Client(
            'tcp://127.0.0.1:6379'
            ."?read_write_timeout=0"
        );
    }

    public function reconnect()
    {
        $this->client = null;
        $this->connect();
    }
}
