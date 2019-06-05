<?php

namespace Database;

class QueryBuilder
{
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function createDatabase() {

    }
}
