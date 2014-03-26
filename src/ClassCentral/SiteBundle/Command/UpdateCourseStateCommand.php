<?php

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Entity\CourseStatus;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Utility\CourseUtility;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates the states of all offerings as well as the next session for each course
 * Class UpdateCourseStateCommand
 * @package ClassCentral\SiteBundle\Command
 */
class UpdateCourseStateCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('classcentral:updatecoursestate')
            ->setDescription('Updates the next session of a course and the state for each offering');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $output->writeln("Updating the state for all offerings");
        $this->updateStates();
        $output->writeln("");

        // Update the Next session for all courses
        $output->writeln("Updating the next sessions for all courses");
        $coursesUpdated = $this->updateNextSession();
        $output->writeln("$coursesUpdated courses updated");
    }

    /**
     * Calculate and update the states of the offering.
     * i.e recent, just announced, upcoming etc.
     */
    private function updateStates()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $offerings = $em->getRepository('ClassCentralSiteBundle:Offering')->findAll();
        $updatedCount = array(); //
        foreach($offerings as $offering)
        {
            // Ignore invalid offerings
            if($offering->getStatus() == Offering::COURSE_NA)
            {
                continue;
            }
            $state = CourseUtility::calculateState($offering);

            if($state == $offering->getState())
            {
               // No change
                continue;
            }

            $offering->setState($state);
            $em->persist($offering);

            // Keep track of different states
            $updatedCount[$state] = isset($updatedCount[$state]) ? $updatedCount[$state] + 1: 1;
        }

        $em->flush();

        // Print out the statistics
        print_r($updatedCount);
    }

    private function updateNextSession()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $courses = $em->getRepository('ClassCentralSiteBundle:Course')->findAll();
        $cnt = 0;
        foreach($courses as $course)
        {
            // Skip courses that are not available
            if( $course->getStatus() == CourseStatus::NOT_AVAILABLE)
            {
                continue;
            }

            $cns = $course->getNextSession(); // Current next session
            $ns = CourseUtility::getNextSession($course); // next session
            if($ns != null)
            {
                // Update the next session
                if($cns != null && $cns->getId() == $ns->getId())
                {
                    // nothing changed
                    continue;
                }
                $course->setNextSession($ns);
                $em->persist($course);
                $cnt++;
            }
        }
        $em->flush();

        return $cnt;
    }
} 