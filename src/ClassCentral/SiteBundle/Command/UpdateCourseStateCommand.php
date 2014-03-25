<?php

namespace ClassCentral\SiteBundle\Command;


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
} 