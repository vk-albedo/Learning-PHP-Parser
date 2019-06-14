<?php


namespace Scripts;

use App\App;
use Database\Database;
use Exception;
use Logging\Logging;
use Redis\Redis;

class ParseAnswers
{
    protected $app;
    protected $connection;
    protected $logger;
    protected $url;
    protected $redis;

    public function __construct()
    {
        $this->app = new App();
        $this->connection = new Database();
        $this->connection->connect(
            App::get('config')['database']
        );
        $this->logger = new Logging();
        $this->redis = new Redis();
    }

    public function __destruct()
    {
        $this->connection = null;
    }

    public function parse($url)
    {
        $this->url = $url;

        $this->logger->log(
            'INFO',
            "Crawling: {$url}",
            __FILE__
        );

        $xpath = $this->app->getXpathFromPage($url);

        $question = $xpath->query(
            "//main[@id='ContentArea']
            /section[@class='Section']
            /div[@class='ContentRow']
            /div[@class='ContentElement Column-100']
            /div[@class='Text']
            /h1
            /span[@id='HeaderString']
            /text()"
        );

        $answers_text = $xpath->query(
            "//main[@id='ContentArea']
            /section[@class='Section']
            /div[@class='ContentRow']
            /div[@class='ContentElement Column-100 NoPadding']
            /table[@id='kxo']
            /tbody
            /tr
            /td[@class='Answer']
            /a
            /text()"
        );

        $symbols = $xpath->query(
            "//main[@id='ContentArea']
            /section[@class='Section']
            /div[@class='ContentRow']
            /div[@class='ContentElement Column-100 NoPadding']
            /table[@id='kxo']
            /tbody
            /tr
            /td[@class='Length']
            /text()"
        );

        $symbols_values = array();
        foreach ($symbols as $value) {
            $symbols_values[] = $value->nodeValue;
        }

        $answers_text_value = array();
        foreach ($answers_text as $value) {
            $answers_text_value[] = $value->nodeValue;
        }

        $answers = array_combine(
            $answers_text_value,
            $symbols_values
        );

        $this->pushToDb($question[0]->textContent, $answers);
    }

    public function pushToDb($question, $answers)
    {
        try {
            $parameters = [
                'text' => $question
            ];
            $this->insertInto('Questions', $parameters);

            $this->logger->log(
                'INFO',
                "Add 1 question to database",
                __FILE__
            );

            foreach ($answers as $key => $value) {
                $parameters = [
                    'text' => $key,
                    'symbols' => $value
                ];
                $this->insertInto('Answers', $parameters);
            }

            $new_answers = sizeof($answers);

            $this->logger->log(
                'INFO',
                "Add {$new_answers} answers to database",
                __FILE__
            );

            $question_id = $this->selectSimple(
                'Questions',
                'id',
                'text',
                $question
            );

            foreach ($answers as $key => $value) {
                $answer_id = $this->selectSimple(
                    'Answers',
                    'id',
                    'text',
                    $key
                );

                $parameters = [
                    'question_id' => $question_id['id'],
                    'answer_id' => $answer_id['id']
                ];
                $this->insertInto('Questions_Answers', $parameters);
            }
        } catch (Exception $exception) {
            $this->logger->log(
                'ERROR',
                "Exception: {$exception->getMessage()}",
                __FILE__
            );
        }
    }

    public function insertInto($table, $parameters)
    {
        try {
            $sql = sprintf(
                "INSERT INTO `%s` (%s) VALUES (%s)",
                $table,
                implode(', ', array_keys($parameters)),
                "'" .implode("', '", array_values($parameters)) ."'"
            );

            $result = $this->connection->execute($sql);

            if (!$result) {
                throw new Exception("Invalid query to database: {$sql}");
            }
        } catch (Exception $exception) {
            $this->logger->log(
                'ERROR',
                "Exception: {$exception->getMessage()}",
                __FILE__
            );

            $this->app->addSetToRedis([$this->url], 'ParseAnswers');

            $this->logger->log(
                'INFO',
                "Return url to list: {$this->url}",
                __FILE__
            );

            exit();
        }
    }

    public function selectSimple($table, $field, $condition, $value)
    {
        try {
            $sql = "SELECT {$field} FROM {$table} WHERE {$condition} = '{$value}'";
            $result = $this->connection->get($sql);

            if (!$result) {
                throw new Exception("Invalid query to database: {$sql}");
            }

            return $result;
        } catch (Exception $exception) {
            $this->logger->log(
                'ERROR',
                "Exception: {$exception->getMessage()}",
                __FILE__
            );

            $this->app->addSetToRedis([$this->url,], 'ParseAnswers');

            $this->logger->log(
                'INFO',
                "Return url to list: {$this->url}",
                __FILE__
            );

            exit();
        }
    }
}
