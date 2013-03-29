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

class ScraperFactory {

    private $initiative;
    private $type = 'updated';
    private $simulate = 'Y';
    private $output;

    public function _construct(Initiative $initiative)
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

    public function getScraper()
    {
        $code = ucwords($this->initiative->getCode());
    }


}