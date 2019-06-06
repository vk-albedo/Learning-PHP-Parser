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
        $statement = $this->pdo->prepare("CREATE DATABASE IF NOT EXISTS `{$config['name']}`;");
        $statement->execute();
    }

    public function createTables($config)
    {
        $statement = $this->pdo->prepare(
            "CREATE TABLE `Questions`(
             `id` INT NOT_NULL AUTO_INCREMENT PRIMARY KEY,
             `text` VARCHAR(455) NOT NULL DEFAULT ''
             );"
        );
        $statement->execute();

        $statement = $this->pdo->prepare(
            "CREATE TABLE `Answers`(
             `id` INT NOT_NULL AUTO_INCREMENT PRIMARY KEY,
             `text` VARCHAR(455) NOT NULL DEFAULT '',
             `symbols` INT NOT_NULL DEFAULT 1
             );"
        );
        $statement->execute();

        $statement = $this->pdo->prepare(
            "CREATE TABLE `Questions_Answers`(
             `question_id` INT NOT_NULL,
             `answer_id` INT NOT_NULL,
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
