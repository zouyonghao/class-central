<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 2/24/14
 * Time: 4:13 PM
 */

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Entity\CourseRecommendation;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates the recommendations tables
 * Class UpdateRecommendationsCommand
 * @package ClassCentral\SiteBundle\Command
 */
class UpdateRecommendationsCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('classcentral:recommender:update')
            ->setDescription('Updates the recommendations from extras/course_clusters.csv');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        gc_enable();
        $recCSV = fopen('extras/course_clusters.csv', 'r');
        $em = $this->getContainer()->get('doctrine')->getManager();

        $em->getConnection()->exec("TRUNCATE TABLE courses_recommendations");
        // clear table of all data before running script
        $output->writeln("DELETING ALL TABLE DATA");

        $numCourses = 0;
        $start = microtime(true);
        while ($row = fgetcsv($recCSV))
        {

            $mainId = $row[0];
            $mainCourse = $em->getRepository('ClassCentralSiteBundle:Course')->find($mainId);

            for ($position = 1; $position <= 10; $position++)
            {

                $recId = $row[$position];

                $recCourse = $em->getRepository('ClassCentralSiteBundle:Course')->find($recId);

                if($mainCourse || $recCourse)
                {
                    $rec = new CourseRecommendation();
                    $rec->setCourse($mainCourse);
                    $rec->setPosition($position);
                    $rec->setRecommendedCourse($recCourse);
                    $em->persist($rec);
                }
                $recCourse = null;
                $rec = null;
            }
            $mainCourse = null;

            $numCourses++;

            if($numCourses%200==0)
            {
                $em->flush();
                $em->clear();
                gc_collect_cycles();
                $output->writeln("Flushing at - " . $numCourses);
                $time_elapsed_secs = microtime(true) - $start;
                $output->writeln("Elasped time to flush: $time_elapsed_secs" );
                $start = microtime(true);
            }

        }
        $em->flush();
        $output->writeln("Flushing at - " . $numCourses);
    }

} 