<?php

namespace Database;

use PDOException;
use PDO;

class Connection
{
    public static function make($config, $first = false)
    {
        try {
            $dbname = (!$first) ? ';dbname='.$config['name'] : '';

            return new PDO(
                $config['connection'].$dbname,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $exception) {
            die($exception->getMessage());
        }
    }
}
