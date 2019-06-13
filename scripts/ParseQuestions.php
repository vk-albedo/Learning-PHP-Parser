<?php


namespace Scripts;

use App\App;
use Logging\Logging;
use Scripts\ParseAnswers;

class ParseQuestions
{
    protected $app;
    protected $logger;

    public function __construct()
    {
        $this->app = new App();
        $this->logger = new Logging();
    }

    public function parse($url)
    {
        $this->logger->log(
            'INFO',
            "Crawling: {$url}",
            __FILE__
        );

        $xpath = $this->app->getXpathFromPage($url);

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

        $new_links = sizeof($questions);

        $this->logger->log(
            'INFO',
            "Crawled: {$url} - get {$new_links} new urls.",
            __FILE__
        );

        $this->app->addSetToRedis($questions, 'ParseAnswers');
    }
}
