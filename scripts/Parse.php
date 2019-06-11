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

                $this->parse_letters($app);

                $app->fork_set(
                    'parse_pages',
                    'letters',
                    $this);

//                var_dump($redis->smembers('letters'));
//                var_dump($redis->smembers('pages'));
//                die();

                $app->fork_set(
                    'parse_questions',
                    'pages',
                    $this);
            case 2:
                $app->fork_set(
                    'parse_answers',
                    'questions',
                    $this);
        }
    }

    public function parse_letters($app)
    {
        $site_url = $app->get('config')['site_url'];

        $xpath = $app->get_xpath_from_page($site_url);

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

        $app->add_set_to_redis($letters, 'letters');
    }

    public function parse_pages($app)
    {
        $redis = $app->get('redis');
        $link = trim($redis->spop('letters'));

        $xpath = $app->get_xpath_from_page($link);

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

        $app->add_set_to_redis($pages, 'pages');
    }

    public function parse_questions($app)
    {
        $redis = $app->get('redis');
        $link = trim($redis->spop('pages'));

        $xpath = $app->get_xpath_from_page($link);

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

        $app->add_set_to_redis($questions, 'questions');
    }

    public function parse_answers($app)
    {
        $redis = $app->get('redis');
        $link = trim($redis->spop('questions'));

        $xpath = $app->get_xpath_from_page($link);

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

        $app->push_to_db($question[0]->textContent, $answers);
    }
}
