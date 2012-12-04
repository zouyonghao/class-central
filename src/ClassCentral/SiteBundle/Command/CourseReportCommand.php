<?php

namespace ClassCentral\SiteBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ClassCentral\SiteBundle\Network\NetworkFactory;

class CourseReportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('classcentral:coursereport')
            ->setDescription('Generates Course Report')
            ->addArgument('month', InputArgument::OPTIONAL,"Which month")
            ->addArgument('year', InputArgument::OPTIONAL, "Which year")
            ->addOption('network',null, InputOption::VALUE_OPTIONAL)
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $month = $input->getArgument('month');
        $year = $input->getArgument('year');

        $offerings = $this
            ->getContainer()->get('doctrine')
            ->getEntityManager()
            ->getRepository('ClassCentralSiteBundle:Offering')
            ->courseReport($month, $year);
        
        
        // Segreagate by Initiative 
        $offeringsByInitiative = array();
        foreach($offerings as $offering)
        {
            $initiative = $offering->getInitiative()->getName();
            $offeringsByInitiative[$initiative][] = $offering;
        }

        $network = NetworkFactory::get( $input->getOption('network'),$output);
        foreach($offeringsByInitiative as $initiative => $offerings)
        {
            $count = count($offerings);
            $network->outInitiative($offerings[0]->getInitiative(), $count);
            $network->beforeOffering();
            foreach($offerings as $offering)
            {               
                $network->outOffering( $offering );
            } 
        }

    }

}
