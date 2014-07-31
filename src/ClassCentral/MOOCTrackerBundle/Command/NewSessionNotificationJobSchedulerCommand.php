<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 7/30/14
 * Time: 4:52 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Command;


use ClassCentral\MOOCTrackerBundle\Job\CourseNewSessionJob;
use ClassCentral\MOOCTrackerBundle\MTHelper;
use ClassCentral\SiteBundle\Entity\UserCourse;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewSessionNotificationJobSchedulerCommand extends ContainerAwareCommand {

    public function configure()
    {
        $this
            ->setName('mooctracker:notification:newsessions')
            ->setDescription('Genrate jobs to send alerts for new courses')
            ->addArgument("date", InputArgument::REQUIRED, "Date on which the job should said eg. Y-m-d");
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln( "<comment>New Sessions notifications scheduler started on {$now->format('Y-m-d H:i:s')}</comment>");

        $esCourses = $this->getContainer()->get('es_courses');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $scheduler = $this->getContainer()->get('scheduler');

        $date = $input->getArgument('date');
        $dateParts = explode('-', $date);
        if( !checkdate( $dateParts[1], $dateParts[2], $dateParts[0] ) )
        {
            $output->writeLn("<error>Invalid date or format. Correct format is Y-m-d</error>");
            return;
        }


        $justAnnouncedCourses = $this->getJustAnnounced();
        $users = MTHelper::getUsersToCoursesMap( $em,$justAnnouncedCourses );
        $output->writeln( "<comment>" . count($users) . ' users found </comment>');

        $scheduled = 0;

        foreach($users as $uid => $courses)
        {
            // Only send notifications for interested courses
            if( !isset($courses[UserCourse::LIST_TYPE_INTERESTED]) )
            {
                continue;
            }

            $id = $scheduler->schedule(
                new \DateTime( $date ),
                CourseNewSessionJob::JOB_TYPE_NEW_SESSION,
                'ClassCentral\MOOCTrackerBundle\Job\CourseNewSessionJob',
                $courses,
                $uid
            );

            if($id){
                $scheduled++;
            }
        }
        $output->writeln( "<info>$scheduled jobs scheduled</info>");
    }

    /**
     * Retrieves ids for just announced courses
     */
    private function getJustAnnounced()
    {
        $finder = $this->getContainer()->get('course_finder');
        $courses = $finder->byTime('recentlyAdded',array(),array(),-1);
        $courseIds = array();
        foreach($courses['hits']['hits'] as $course)
        {
            // Send notification for courses which have new sessions, not the ones that
            // were just added
            $sessions = count($course['_source']['sessions']);
            if ( $sessions > 1 && $sessions <= 6) // Ignore sessions that are repeated often
            {
                $courseIds[] = $course['_source']['id'];
            }
        }
        return $courseIds;
    }

}