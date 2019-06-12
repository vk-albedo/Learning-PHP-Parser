<?php


namespace Database;

use Logging\Logging;
use PDO;
use PDOException;

class Database
{
    protected $pdo;
    protected $logger;

    public function __construct()
    {
        $this->logger = new Logging();
    }

    public function __destruct()
    {
        $this->pdo = null;
    }

    public function connect($config, $first = false)
    {
        try {
            $dbname = (!$first) ? ';dbname=' . $config['name'] : '';

            $this->pdo = new PDO(
                $config['connection'] . $dbname,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $exception) {
            die($exception->getMessage());
        }
    }

    public function reconnect($config)
    {
        $this->pdo = null;
        $this->connect($config);
    }

    public function get($statement)
    {
        $result = $this->pdo->query($statement);

        if (!$result) {
            $this->logger->log(
                'ERROR',
                'Failed: PDO::query()',
                __FILE__
            );
        }

        return $result;
    }

    public function execute($statement)
    {
        $this->pdo->exec($statement);
    }
}
