<?php

namespace ClassCentral\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ClassCentral\SiteBundle\Network\NetworkFactory;

class CourseStatsCommand extends ContainerAwareCommand{
    
    protected function configure() {
          $this
            ->setName('classcentral:coursestats');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output){
        $offerings = $this
            ->getContainer()->get('doctrine')
            ->getEntityManager()
            ->getRepository('ClassCentralSiteBundle:Offering')
            ->courseStats();
        
        print_r($offerings);
    }
}