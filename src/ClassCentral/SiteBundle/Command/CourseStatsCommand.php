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
        /*
        $stats = $this
            ->getContainer()->get('doctrine')
            ->getManager()
            ->getRepository('ClassCentralSiteBundle:Offering')
            ->courseStats();
        
        print_r($stats);

        $totalCount = 0;
        ksort($stats);
        foreach($stats as $year => $months)
        {
            ksort($months);
            foreach($months as $month => $count)
            {
                $totalCount += $count;
                echo sprintf("[Date.UTC(%s, %s, 1), %s ]",$year,$month,$totalCount).",\n";
            }
        }
        */

        $liveCourses = $this
            ->getContainer()->get('doctrine')
            ->getEntityManager()
            ->getRepository('ClassCentralSiteBundle:Offering')
            ->getAllLiveCourses();

        $offeredStats = array();
        foreach($liveCourses as $course)
        {
            $offeredStats[count($course->getOfferings())]++;
        }

        print_r($offeredStats);
        /*
        $universities = array();
        $institutions = array();
        $instructors = array();


        foreach($liveCourses as $course)
        {
            foreach($course->getInstructors() as $instructor)
            {
                $instructors[] = $instructor->getId();
            }

            foreach($course->getInstitutions() as $institution)
            {
                if($institution->getIsUniversity())
                {
                    $universities[] = $institution->getId();
                }
                else
                {
                    $institutions[] = $institution->getId();
                    echo $institution->getName(). " --- ";
                }
            }
        }

        echo "\nUniversities : " . count(array_unique($universities)). "\n";
        echo "Institutions:  " . count(array_unique($institutions)). "\n";
        echo "Instructors:   " . count(array_unique($instructors)). "\n";
        */


    }
}