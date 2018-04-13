<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dhawal
 * Date: 3/24/13
 * Time: 12:51 AM
 * To change this template use File | Settings | File Templates.
 */

namespace ClassCentral\ScraperBundle\Scraper;

use ClassCentral\ScraperBundle\Utility\DBHelper;
use ClassCentral\SiteBundle\Entity\Initiative;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ScraperAbstractInterface
{
    /** @var  Initiative $initiative */
    protected $initiative;
    protected $type = 'updated';
    protected $simulate = 'Y';
    protected $isCredential = false;
    /** @var  OutputInterface $output */
    protected $output;
    protected $created; // If true, new offerings can be added
    protected $updated; // If true,  offerings can only be updated
    protected $modify; // If true. database can be modified

    /** @var  ContainerInterface $container */
    protected $container;
    /** @var  DBHelper $dbHelper */
    protected $dbHelper;

    public function setIsCredential( $isCredential )
    {
        $this->isCredential = $isCredential;
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

    public function setInitiative(Initiative $initiative)
    {
        $this->initiative = $initiative;
    }

    public function getInitiative()
    {
        return $this->initiative;
    }
    

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function doModify()
    {
        return $this->modify;
    }

    public function doCreate()
    {
        return $this->created;
    }

    public function doUpdate()
    {
        return $this->updated;
    }

    public function init()
    {
        $this->created = ($this->type == 'add'); // Courses are to be created
        $this->updated = ($this->type == 'update'); // Courses are to be updated
        $this->modify = ($this->simulate == 'N') ; // Database is to be modified
        $this->dbHelper = new DBHelper();
        $this->dbHelper->setScraper($this);
    }

    public function getManager()
    {
       return $this->container->get('doctrine')->getManager();
    }

    public function out($str)
    {
        $this->output->writeln($str);
    }

    abstract public function scrape();
}