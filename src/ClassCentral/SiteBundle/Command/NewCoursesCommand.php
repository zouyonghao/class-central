<?php

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Entity\Item;
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
        $formatter = $this->getContainer()->get('course_formatter');

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


        // sort courses based on follows
        uasort($courses,function ($c1,$c2){
            $follow = $this->getContainer()->get('follow');
            $c1Counts = 0;
            foreach ($c1->getInstitutions() as $ins)
            {
                $c1Counts += $follow->getNumFollowers(Item::ITEM_TYPE_INSTITUTION,$ins->getId());
            }
            $c2Counts = 0;
            foreach ($c2->getInstitutions() as $ins)
            {
                $c2Counts += $follow->getNumFollowers(Item::ITEM_TYPE_INSTITUTION,$ins->getId());
            }

            return $c1Counts < $c2Counts;
        });


        foreach($courses as $course)
        {
            if($course->getStatus() >= 100)
            {
                // Course is not available or is under review
                continue;
            }

            if( !$course->getIsMooc() )
            {
                continue;
            }

            if( $course->getPrice() != 0)
            {
                continue;
            }

            $isUniversity = false;
            if($course->getInstitutions())
            {
                foreach ($course->getInstitutions() as $ins)
                {
                    if($ins->getIsUniversity())
                    {
                        $isUniversity = true;
                        break;
                    }
                }
            }

            if($isUniversity)
            {
                $subject = $course->getStream();
                if($subject->getParentStream())
                {
                    $subject = $subject->getParentStream();
                }

                $groups[$subject->getName()][] = $course;
            }
        }


        $count = 0;
        $universities = [];
        foreach($groups as $insName => $insCourses)
        {
            $output->writeln("<h2>" . strtoupper($insName)."</h2>");
            foreach($insCourses as $course)
            {

                $count++;
                if (empty($universities[$course->getInstitutions()->first()->getName()]))
                {
                    $universities[$course->getInstitutions()->first()->getName()] = 0;
                }
                $universities[$course->getInstitutions()->first()->getName()]++;
                echo $formatter->emailFormat($course);
            }

            $output->writeln( "<br/>");
        }
        $numUniversities = count($universities);
        $output->writeLn( " $count courses added " );
        $output->writeLn( " $numUniversities universities added " );
        arsort($universities);
        var_dump($universities);
}
}