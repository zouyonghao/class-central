<?php

namespace ClassCentral\ScraperBundle\Command;


use ClassCentral\SiteBundle\Entity\Offering;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EdxSelfPacedCleanUpCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName("classcentral:edx:selfpaced-cleanup")
            ->setDescription("Change the status of older offerings from selfpaced to sessions")
            ->addOption('simulate',null,InputOption::VALUE_OPTIONAL,"N if database needs to be modified. Defaults to Y") // value is Y or N
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $simulate = $input->getOption("simulate");
        if(empty($simulate) || $simulate != 'N')
        {
            $simulate = 'Y';
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $edXProvider = $em->getRepository('ClassCentralSiteBundle:Initiative')->findOneBy(['code'=>'edx']);
        $edXCourses = $em->getRepository('ClassCentralSiteBundle:Course')->findBy(['initiative' => $edXProvider]);
        $coursesWithDuplicateSelfPacedSessions = 0;
        foreach ($edXCourses as $course)
        {
            $selfPaced = 0;
            // Filter out the selfpaced courses
            $selfPacedOfferings = $course->getOfferings()->filter(function ($offering){
                return $offering->getStatus() == Offering::COURSE_OPEN;
            });

            if($selfPacedOfferings->count() > 1)
            {
                $coursesWithDuplicateSelfPacedSessions++;
                $output->writeln("{$course->getId()} - {$course->getName()}");

                // Change the status of older offerings
                // sort the offerings by start date
                $iterator = $selfPacedOfferings->getIterator();
                $iterator->uasort(function ($o1,$o2) {
                    return $o1->getStartDate() < $o2->getStartDate();
                });
                $selfPacedOfferings = new ArrayCollection(iterator_to_array($iterator));

                $currentOffering = $selfPacedOfferings->first();
                $output->writeln("CURRENT OFFERING : " . $currentOffering->getId() . ' - ' . $currentOffering->getDisplayDate());
                $selfPacedOfferings->removeElement($currentOffering);
                foreach ($selfPacedOfferings as $offering)
                {
                    $output->writeln($offering->getId() . ' - ' . $offering->getDisplayDate());
                    if($simulate == 'N')
                    {
                        // Update the status to start dates known
                        $offering->setStatus(Offering::START_DATES_KNOWN);
                        $em->persist($offering);
                    }
                }
            }
        }
        $em->flush();
        $output->writeln("$coursesWithDuplicateSelfPacedSessions courses found");
    }
}