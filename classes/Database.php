<?php


namespace Database;

use Exception;
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
        try {
            $result = $this->pdo->query($statement);
            $result = $result->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                throw new PDOException('Failed: PDO::query()');
            }

            return $result;
        } catch (PDOException $exception) {
            $this->logger->log(
                'ERROR',
                "Exception: {$exception->getMessage()}",
                __FILE__
            );

            return false;
        }
    }

    public function execute($statement)
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if ($errno === E_WARNING) {
                $this->logger->log(
                    'ERROR',
                    $errstr,
                    __FILE__
                );
                return true;
            } else {
                // fallback to default php error handler
                return false;
            }
        });

        try {
            $result = $this->pdo->exec($statement);

            if ($result === false) {
                throw new PDOException('Failed: PDO::exec()');
            }

            return true;
        } catch (PDOException $exception) {
            $this->logger->log(
                'ERROR',
                "Exception: {$exception->getMessage()}",
                __FILE__
            );

            return false;
        }
    }
}
