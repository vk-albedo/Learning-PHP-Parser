<?php


namespace Scripts;

use App\App;
use Database\Database;
use Logging\Logging;

class ParseAnswers
{
    protected $app;
    protected $connection;
    protected $logger;

    public function __construct()
    {
        $this->app = new App();
        $this->connection = new Database();
        $this->connection->connect(
            App::get('config')['database']
        );
        $this->logger = new Logging();
    }

    public function __destruct()
    {
        $this->connection = null;
    }

    public function parse($url)
    {
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
        $parameters = [
            'text' => $question
        ];
        $this->insertInto('Questions', $parameters);

        $this->logger->log(
            'INFO',
            "Add question to database",
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
                'question_id' => $question_id[0]->id,
                'answer_id' => $answer_id[0]->id
            ];
            $this->insertInto('Questions_Answers', $parameters);
        }
    }

    public function insertInto($table, $parameters)
    {
        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $table,
            implode(', ', array_keys($parameters)),
            ':' . implode(', :', array_keys($parameters))
        );

        $this->connection->execute($sql);
    }

    public function selectSimple($table, $field, $condition, $value)
    {
        $sql = "SELECT {$field} FROM {$table} WHERE {$condition} = '{$value}'";

        return $this->connection->get($sql);
    }
}
