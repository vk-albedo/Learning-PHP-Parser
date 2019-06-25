<?php


namespace Database;

use App\App;
use Logging\Logging;
use PDO;
use PDOException;

class Database
{
    protected $pdo;
    protected $logger;
    protected $warning = false;

    public function __construct($first = false)
    {
        $this->logger = new Logging();
        $this->connect($first);
    }

    public function __destruct()
    {
        $this->pdo = null;
    }

    protected function connect($first = false)
    {
        try {
            $config = App::get('config')['database'];

            $dbname = (!$first) ? ';dbname=' . $config['name'] : '';

            $this->pdo = new PDO(
                $config['connection'] . $dbname,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $exception) {
            $this->logger->log(
                'ERROR',
                "Exception: {$exception->getMessage()}",
                __FILE__
            );
            exit();
        }
    }

    public function reconnect()
    {
        $this->pdo = null;
        $this->connect();
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
                $dom_str = 'Integrity constraint violation: 1062 Duplicate entry';
                $pos = strpos($errstr, $dom_str);

                if ($pos !== false) {
                    $this->warning = true;
                }

                return true;
            } else {
                // fallback to default php error handler
                return false;
            }
        });

        try {
            $result = $this->pdo->exec($statement);

            if ($result === false && $this->warning == false) {
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
