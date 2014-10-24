<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 10/23/14
 * Time: 5:15 PM
 */

namespace ClassCentral\SiteBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates a list in OpenCulture formatting
 * Class CourseReportOCCommand
 * @package ClassCentral\SiteBundle\Command
 */
class CourseReportOCCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('classcentral:openculture')
            ->setDescription('Generates a Course Report to match open cultures formatting. Can be used only for upcoming courses')
            ->addArgument('month', InputArgument::OPTIONAL,"Which month")
            ->addArgument('year', InputArgument::OPTIONAL, "Which year")

        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $esCourses = $this->getContainer()->get('es_courses');
        $em = $this->getContainer()->get('doctrine')->getManager();

        $month = $input->getArgument('month');
        $year = $input->getArgument('year');
        $dt = new \DateTime;
        if (!$month) {
            $month = $dt->format('m');
        }
        if (!$year) {
            $year = $dt->format('Y');
        }

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $start = new \DateTime("$year-$month-01");
        $end = new \DateTime("$year-$month-$daysInMonth");

        $results = $esCourses->findByNextSessionStartDate($start, $end);

        foreach($results['results']['hits']['hits'] as $course)
        {
            $c = $course['_source'];
            $output->writeln( $this->getHtml($c) );
        }

        $output->writeLn($results['results']['hits']['total'] . " courses");
    }

    private function getHtml( $course )
    {
        $format = '<li><a href="%s">%s</a> (SA/VC$) -%s %s - %s %s</li>';

        // Course Name
        $name = trim($course['name']);

        // Course Url
        $url  = $course['nextSession']['url'];

        // Name of the Institution
        $institutionName = '';
        if( !empty($course['institutions']) )
        {
            $ins = array_pop($course['institutions']);
            $institutionName = ' ' .$ins['name'] . ' on';
        }

        $provider = $course['provider']['name'];
        $dt = new \DateTime( $course['nextSession']['startDate']);
        $date = $dt->format('F j');

        $length ='' ;
        if( $course['length'] == 0)
        {
            // Do nothing
        }
        else
        {
            $length = "({$course['length']} weeks)";
        }

        return sprintf($format, $url,$name, $institutionName, $provider, $date, $length);
    }
}