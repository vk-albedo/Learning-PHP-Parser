<?php

namespace Database;

use Exception;
use PDO;

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

    public function selectSimple($table, $field, $condition, $value)
    {
        $statement = $this->pdo->prepare(
            "SELECT {$field} 
             FROM {$table} 
             WHERE {$condition} = '{$value}'"
        );
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_CLASS);
    }

    public function insertInto($table, $parameters)
    {
        $sql = sprintf(
            "INSERT INTO `%s` (%s) 
            VALUES (%s)",
            $table,
            implode(', ', array_keys($parameters)),
            ':' . implode(', :', array_keys($parameters))
        );

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);
        } catch (Exception $exception) {
            die('Wrong type data.');
        }
    }

    public function addElement($question, $answers)
    {
//        TODO: add right $parameters

        echo "\n".$question ."\n";

        $parameters = [
            'text' => $question
        ];
        $this->insertInto('Questions', $parameters);

        foreach ($answers as $key => $value) {
            $parameters = [
                'text' => $key,
                'symbols' => $value
            ];
            $this->insertInto('Answers', $parameters);
        }

        $question_id = $this->selectSimple(
            'Questions',
            'id',
            'text',
            $question
        );

        var_dump($question_id);

        foreach ($answers as $key => $value) {
            $answer_id = $this->selectSimple(
                'Answers',
                'id',
                'text',
                $key
            );

            $parameters = [
                'question_id' => $question_id[0]->id,
                'answer_id' => $answer_id[0]->id
            ];
            $this->insertInto('Questions_Answers', $parameters);
        }
    }
}
