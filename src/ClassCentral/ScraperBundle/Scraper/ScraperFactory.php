<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dhawal
 * Date: 3/24/13
 * Time: 12:26 AM
 * To change this template use File | Settings | File Templates.
 */

namespace ClassCentral\ScraperBundle\Scraper;

use ClassCentral\SiteBundle\Entity\Initiative;
use Symfony\Component\Console\Output\OutputInterface;
use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;

class ScraperFactory {

    private $initiative;
    private $type = 'updated';
    private $simulate = 'Y';
    private $output;
    private $domParser;
    private $container;

    public function __construct(Initiative $initiative)
    {
        $this->initiative = $initiative;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setSimulate($simulate)
    {
        $this->simulate = $simulate;
    }

    public function setOutputInterface(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function setDomParser($domParser)
    {
        $this->domParser = $domParser;
    }

    public function getScraper()
    {
        $code = ucwords(strtolower($this->initiative->getCode()));
        $class = "ClassCentral\\ScraperBundle\\Scraper\\$code\\Scraper";
        $obj = new $class();
        $this->initiativeScraper($obj);

        return $obj;
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    private function initiativeScraper(ScraperAbstractInterface $obj)
    {
        $obj->setType($this->type);
        $obj->setSimulate($this->simulate);
        $obj->setOutputInterface($this->output);
        $obj->setInitiative($this->initiative);
        $obj->setDomParser($this->domParser);
        $obj->setContainer($this->container);
        $obj->init();
    }


}