<?php


namespace Scripts;

use App\App;
use Logging\Logging;

class ParseQuestions implements IParse
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

        $host = App::get('config')['host'];
        $questions_link = [];
        foreach ($questions as $question) {
            $questions_link[] = $host . $question->textContent;
        }

        $objects = $this->app->encodeToJSON($questions_link, 'ParseAnswers');
        $this->app->addSetToRedis($objects);
    }
}
