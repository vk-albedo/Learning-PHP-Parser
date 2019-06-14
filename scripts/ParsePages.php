<?php


namespace Scripts;

use App\App;
use Logging\Logging;

class ParsePages
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

        $new_links = sizeof($pages);

        $this->logger->log(
            'INFO',
            "Crawled: {$url} - get {$new_links} new urls.",
            __FILE__
        );

        $host = App::get('config')['host'];
        $pages_link = [];
        foreach ($pages as $page) {
            $pages_link[] = $host . $page->textContent;
        }

        $this->app->addSetToRedis($pages_link, 'ParseQuestions');
    }
}
