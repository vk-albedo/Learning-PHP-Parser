<?php

namespace Scripts;

use App\App;

class Parse
{
    public function parse($mode)
    {
        $app = new App();

        switch ($mode) {
            case 1:
                $redis = $app->get('redis');

                $this->parseLetters($app);

                $app->forkSet(
                    'parsePages',
                    'letters',
                    $this);

//                var_dump($redis->smembers('letters'));
//                var_dump($redis->smembers('pages'));
//                die();

                $app->forkSet(
                    'parseQuestions',
                    'pages',
                    $this);
            case 2:
                $app->forkSet(
                    'parseAnswers',
                    'questions',
                    $this);
        }
    }

    public function parseLetters($app)
    {
        $site_url = $app->get('config')['site_url'];

        $xpath = $app->getXpathFromPage($site_url);

        $letters = $xpath->query(
            "//main[@id='ContentArea']
            /section[@class='Section']
            /div[@class='ContentRow']
            /div[@class='ContentElement Column-100 Color Padding']
            /ul[@class='dnrg']
            /li
            /a
            /@href"
        );

        $app->addSetToRedis($letters, 'letters');
    }

    public function parsePages($app)
    {
        $redis = $app->get('redis');
        $link = trim($redis->spop('letters'));

        $xpath = $app->getXpathFromPage($link);

        $pages = $xpath->query(
            "//main[@id='ContentArea']
            /section[@class='Section']
            /div[@class='ContentRow']
            /div[@class='ContentElement Column-100 Color Padding']
            /div[@class='Text']
            /ul[@class='dnrg']
            /li
            /a
            /@href"
        );

        $app->addSetToRedis($pages, 'pages');
    }

    public function parseQuestions($app)
    {
        $redis = $app->get('redis');
        $link = trim($redis->spop('pages'));

        $xpath = $app->getXpathFromPage($link);

        $questions = $xpath->query(
            "//main[@id='ContentArea']
            /section[@class='Section']
            /div[@class='ContentRow']
            /div[@class='ContentElement Column-100']
            /div[@class='Text']
            /table
            /tbody
            /tr
            /td[@class='Question']
            /a
            /@href"
        );

        $app->addSetToRedis($questions, 'questions');
    }

    public function parseAnswers($app)
    {
        $redis = $app->get('redis');
        $link = trim($redis->spop('questions'));

        $xpath = $app->getXpathFromPage($link);

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
        foreach($symbols as $value) {
            $symbols_values[] = $value->nodeValue;
        }
        $answers_text_value = array();
        foreach($answers_text as $value) {
            $answers_text_value[] = $value->nodeValue;
        }

        $answers = array_combine(
            $answers_text_value,
            $symbols_values
        );

        $app->pushToDb($question[0]->textContent, $answers);
    }
}
