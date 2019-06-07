<?php

namespace Database;

class QueryBuilder
{
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function createDatabase($config)
    {
        $statement = $this->pdo->prepare(
            "CREATE DATABASE IF NOT EXISTS `{$config['name']}`;"
        );
        $statement->execute();
    }

    public function createTables($config)
    {
        $statement = $this->pdo->prepare(
            "USE `{$config['name']}`;"
        );
        $statement->execute();

        $statement = $this->pdo->prepare(
            "CREATE TABLE IF NOT EXISTS `Questions`(
             `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
             `text` VARCHAR(455) NOT NULL DEFAULT ''
             );"
        );
        $statement->execute();

        $statement = $this->pdo->prepare(
            "CREATE TABLE IF NOT EXISTS `Answers`(
             `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
             `text` VARCHAR(455) NOT NULL DEFAULT '',
             `symbols` INT NOT NULL DEFAULT 0
             );"
        );
        $statement->execute();

        $statement = $this->pdo->prepare(
            "CREATE TABLE IF NOT EXISTS `Questions_Answers`(
             `question_id` INT NOT NULL,
             `answer_id` INT NOT NULL,
             PRIMARY KEY (`question_id`, `answer_id`),
             CONSTRAINT `FK_Questions` FOREIGN KEY (`question_id`)
                REFERENCES `Questions` (`id`) ON DELETE CASCADE,
             CONSTRAINT `FK_Answers` FOREIGN KEY (`answer_id`)
                REFERENCES `Answers` (`id`) ON DELETE CASCADE
             );"
        );
        $statement->execute();
    }
}
