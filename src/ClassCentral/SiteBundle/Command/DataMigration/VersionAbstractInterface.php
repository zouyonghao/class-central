<?php

namespace ClassCentral\SiteBundle\Command\DataMigration;

use \Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class VersionAbstractInterface
{
    /**
     * @var ContainerInterface
     */
    protected  $container;
    
    /**
     * @var OutputInterface
     * @param 
     */
    protected $output;

    public function __construct(ContainerInterface $container, OutputInterface $output)
    {
        $this->container = $container;
        $this->output =  $output;
    }

    abstract public function migrate();
    
}
