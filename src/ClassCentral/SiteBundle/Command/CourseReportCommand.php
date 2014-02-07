<?php

namespace ClassCentral\SiteBundle\Command;

use ClassCentral\SiteBundle\Command\Network\RedditNetwork;
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
            ->addOption('cs',null, InputOption::VALUE_OPTIONAL, "Yes/No - Splits the cs courses up by different levels")
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $month = $input->getArgument('month');
        $year = $input->getArgument('year');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $network = NetworkFactory::get( $input->getOption('network'),$output);
        $isReddit = ($input->getOption('network') == 'Reddit') || ($input->getOption('cs') == 'Yes');
        $courseToLevelMap = RedditNetwork::getCourseToLevelMap();

        $offerings = $this
            ->getContainer()->get('doctrine')
            ->getManager()
            ->getRepository('ClassCentralSiteBundle:Offering')
            ->courseReport($month, $year);
        
        
        // Segreagate by Initiative 
        $offeringsByInitiative = array();
        $offeringsByStream = array();
        $offeringsByLevel = array(
            'beginner' => array(),
            'intermediate' => array(),
            'advanced' => array(),
            'uncategorized' => array()
        );
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
                //continue;
            }


            $offeringsByInitiative[$initiative][] = $offering;
            $subject = $offering->getCourse()->getStream();
            if($subject->getParentStream())
            {
                $subject = $subject->getParentStream();
            }
            $offeringsByStream[$subject->getName()][] = $offering;

            if($isReddit && $subject->getName() == 'Computer Science')
            {
                $courseId = $offering->getCourse()->getId();
                if(isset($courseToLevelMap[$courseId]))
                {
                    $offeringsByLevel[$courseToLevelMap[$courseId]][] = $offering;
                }
                else
                {
                    $offeringsByLevel['uncategorized'][] = $offering;
                }
            }
        }

        // Segregate by Stream


        $network->setRouter($this->getContainer()->get('router'));
        $coursesByCount = array();

        if($isReddit)
        {
            foreach($offeringsByLevel as $level => $offerings)
            {
                $count = count($offerings);
                $network->outLevel(ucfirst($level), $count);
                $network->beforeOffering();

                foreach($offerings as $offering)
                {
                    $network->outOffering( $offering );
                    // Count the number of times its been added to my courses
                    $added = $em->getRepository('ClassCentralSiteBundle:UserCourse')->findBy(array('course' => $offering->getCourse()));
                    $timesAdded = count($added);
                    $coursesByCount[$offering->getCourse()->getName()] = $timesAdded;
                }
            }
        }
        else
        {
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
                    // Count the number of times its been added to my courses
                    $added = $em->getRepository('ClassCentralSiteBundle:UserCourse')->findBy(array('course' => $offering->getCourse()));
                    $timesAdded = count($added);
                    $coursesByCount[$offering->getCourse()->getName()] = $timesAdded;
                }
            }
        }


        asort($coursesByCount);
        print_r($coursesByCount);

    }



}
