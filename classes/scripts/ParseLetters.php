<?php


namespace Scripts;

use App\App;
use Logging\Logging;

class ParseLetters implements IParse
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

        $new_links = sizeof($letters);

        $this->logger->log(
            'INFO',
            "Crawled: {$url} - get {$new_links} new urls.",
            __FILE__
        );

        $host = App::get('config')['host'];
        $letters_link = [];
        foreach ($letters as $letter) {
            $letters_link[] = $host . $letter->textContent;
        }

        $objects = $this->app->encodeToJSON($letters_link, 'ParsePages');
        $this->app->addSetToRedis($objects);
    }
}
