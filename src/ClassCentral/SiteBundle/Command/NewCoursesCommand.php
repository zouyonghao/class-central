<?php

namespace ClassCentral\SiteBundle\Command;


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

        foreach($courses as $course)
        {
            $path = $router->generate('ClassCentralSiteBundle_mooc', array('id' => $course->getId(),'slug'=>$course->getSlug()));
            $output->writeln($course->getName());

            $output->writeln('https://www.class-central.com'. $path);
            $output->writeln('');
        }

    }
}