<?php

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Entity\Offering;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class NewCoursesCommand
 * Gets all new courses since the specified date. If no date is
 * specified, it retrieves the courses in last 2 weeks
 * @package ClassCentral\SiteBundle\Command
 */
class NewCoursesCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName("classcentral:newcourses")
            ->setDescription("Generates a list of courses in last 2 weeks")
            ->addArgument('date', InputArgument::OPTIONAL,"Which date? eg. mm/dd/yyyy");
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $router = $this->getContainer()->get('router');
        $date = $input->getArgument('date');

        if($date)
        {

            $dt = \DateTime::createFromFormat("m/d/Y",$date) ;
        }
        else
        {
            // Nothing specified. Pick a date 2 weeks ago
            $dt = new \DateTime();
            $dt->sub(new \DateInterval("P14D"));
        }

        $courses = $this
            ->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('ClassCentralSiteBundle:Course')
            ->getNewCourses($dt);



        $groups = array();
        foreach($courses as $course)
        {
            if($course->getStatus() >= 100)
            {
                // Course is not available or is under review
                continue;
            }
            $ins = $course->getInitiative();
            $group = 'Independent';
            if($ins)
            {
                $group = $ins->getName();
            }
            $groups[$group][] = $course;

        }

        $count = 0;
        foreach($groups as $insName => $insCourses)
        {
            $output->writeln(strtoupper($insName)."<br/>");
            foreach($insCourses as $course)
            {

                $count++;
                $path = $router->generate('ClassCentralSiteBundle_mooc', array('id' => $course->getId(),'slug'=>$course->getSlug()));
                $name = $course->getName();
                $url = 'https://www.class-central.com'. $path;
                $output->writeln("<a href='$url'>$name</a><br/>");

                $secondLine = array();
                $nextSession = $course->getNextOffering();
                if ($nextSession->getStatus() == Offering::START_DATES_KNOWN)
                {
                    $secondLine[] = $nextSession->getStartDate()->format('M jS, Y');
                }
                elseif($nextSession->getStatus() == Offering::COURSE_OPEN)
                {
                    $secondLine[] = 'Self Paced';
                }


                $ins = $course->getInstitutions();
                $insName ='';
                if($ins[0])
                {
                    $insName = $ins[0]->getName();

                }
                $secondLine[] = $insName;
                if (!empty($secondLine))
                {
                    $output->writeln("<i>" . implode(' | ', $secondLine) . "</i><br/>");
                }
            }

            $output->writeln( "<br/>");
        }

        $output->writeLn( " $count courses added " );
}
}