<?php

namespace ClassCentral\SiteBundle\Command;

use ClassCentral\SiteBundle\Entity\Offering;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ClassCentral\SiteBundle\Command\Network\NetworkFactory;

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
            ->getManager()
            ->getRepository('ClassCentralSiteBundle:Offering')
            ->courseReport($month, $year);
        
        
        // Segreagate by Initiative 
        $offeringsByInitiative = array();
        $offeringsByStream = array();
        foreach($offerings as $offering)
        {
            if($offering->getInitiative() == null)
            {
                $initiative = 'Others';
            }
            else
            {
                $initiative = $offering->getInitiative()->getName();
            }

            // Skip self paced courses
            if($offering->getStatus() == Offering::COURSE_OPEN)
            {
                continue;
            }


            $offeringsByInitiative[$initiative][] = $offering;
            $subject = $offering->getCourse()->getStream();
            if($subject->getParentStream())
            {
                $subject = $subject->getParentStream();
            }
            $offeringsByStream[$subject->getName()][] = $offering;
        }

        // Segregate by Stream


        $network = NetworkFactory::get( $input->getOption('network'),$output);
        $network->setRouter($this->getContainer()->get('router'));
        foreach($offeringsByStream as $stream => $offerings)
        {
            $subject = $offerings[0]->getCourse()->getStream();
            if($subject->getParentStream())
            {
                $subject = $subject->getParentStream();
            }
            $count = count($offerings);
            $network->outInitiative($subject, $count);
            $network->beforeOffering();
            foreach($offerings as $offering)
            {               
                $network->outOffering( $offering );
            } 
        }

    }

}
