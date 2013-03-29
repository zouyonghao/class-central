<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dhawal
 * Date: 3/24/13
 * Time: 12:51 AM
 * To change this template use File | Settings | File Templates.
 */

namespace ClassCentral\ScraperBundle\Scraper;

use ClassCentral\SiteBundle\Entity\Initiative;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ScraperAbstractInterface
{
    private $initiative;
    private $type = 'updated';
    private $simulate = 'Y';
    private $output;


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

    abstract public function scrape();
}