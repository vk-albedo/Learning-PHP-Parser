<?php

namespace Scripts;

use App\App;
use Exception;

class Parse
{
    public static function parse()
    {
        self::parse_letters();

        self::parse_pages();

        self::parse_questions();

        self::parse_answers();
    }

    public static function parse_letters()
    {
        try {
            $site_url = App::get('config')['site_url'];

            $xpath = App::get_xpath_from_page($site_url);

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

            App::add_set_to_redis($letters, 'letters');

        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public static function parse_pages()
    {
        try {
            $redis = App::get('redis');
            $link = trim($redis->spop('letters'));

            $xpath = App::get_xpath_from_page($link);

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

            App::add_set_to_redis($pages, 'pages');

        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public static function parse_questions()
    {
        try {
            $redis = App::get('redis');
            $link = trim($redis->spop('pages'));

            $xpath = App::get_xpath_from_page($link);

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
                @href"
            );

            App::add_set_to_redis($questions, 'questions');

        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public static function parse_answers()
    {
        try {
            $redis = App::get('redis');
            $link = trim($redis->spop('questions'));

            $xpath = App::get_xpath_from_page($link);

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

            $answers = array_combine(
                iterator_to_array($answers_text),
                iterator_to_array($symbols)
            );

            App::push_to_db($question, $answers);

        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }
}
