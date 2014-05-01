<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/16/14
 * Time: 2:47 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Command;


use ClassCentral\MOOCTrackerBundle\Job\CourseStartReminderJob;
use ClassCentral\SiteBundle\Entity\UserPreference;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CourseStartReminderJobSchedulerCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('mooctracker:reminders:coursestart')
            ->setDescription("Generate jobs for course start")
            ->addArgument('type', InputArgument::REQUIRED, "email_reminder_course_start_2weeks/email_reminder_course_start_1day")
            ->addArgument("date", InputArgument::REQUIRED, "Date for which the jobs should be generated eg. Y-m-d");

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln( "<comment>Create reminder scheduler started on {$now->format('Y-m-d H:i:s')}</comment>");

        $esCourses = $this->getContainer()->get('es_courses');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $scheduler = $this->getContainer()->get('scheduler');

        // Parse the input arguments
        $type = $input->getArgument('type');
        $date = $input->getArgument('date'); // The date at which the job is to be run
        if($type != CourseStartReminderJob::JOB_TYPE_1_DAY_BEFORE && $type != CourseStartReminderJob::JOB_TYPE_2_WEEKS_BEFORE)
        {
            // Invalid job type
            $output->writeln("<error>Invalid job type. Valid types are email_reminder_course_start_2weeks/email_reminder_course_start_1day</error>");
            return;
        }
        $dateParts = explode('-', $date);
        if( !checkdate( $dateParts[1], $dateParts[2], $dateParts[0] ) )
        {
            $output->writeLn("<error>Invalid date or format. Correct format is Y-m-d</error>");
            return;
        }

        $dt = new \DateTime( $date);
        if( $type == CourseStartReminderJob::JOB_TYPE_2_WEEKS_BEFORE )
        {
            // Find courses starting 2 weeks (14 days after the current date)
            $dt->add( new \DateInterval('P14D') );
        }
        elseif( $type == CourseStartReminderJob::JOB_TYPE_1_DAY_BEFORE )
        {
            // Find courses starting 1 day later
            $dt->add( new \DateInterval('P1D') );
        }

        $output->writeln( "<comment>$type - {$dt->format('Y-m-d')}</comment>" );

        $results = $esCourses->findByNextSessionStartDate($dt, $dt);
        $output->writeln("<comment>". $results['results']['hits']['total'] . ' courses starting on ' . $dt->format( 'Y-m-d' ) . " <comment>");
        if($results['results']['hits']['total'] == 0)
        {
            $output->writeln("<info>No courses starting on {$dt->format('Y-m-d')}</info>");
            return;
        }

        $courseIds = array();
        foreach($results['results']['hits']['hits'] as $course)
        {
            $courseIds[] = $course['_id'];
        }

        // Get a list of all users and build a user to courses map
        $qb = $em->createQueryBuilder();
        $mtUserPreference = UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES;
        $qb->add('select', 'uc as usercourse, c.id as cid,u.id as uid, u.isverified as verified')
             ->add('from', 'ClassCentralSiteBundle:UserCourse uc')
             ->join('uc.course', 'c')
            ->join('uc.user', 'u')
            ->join('u.userPreferences', 'up')
            ->andWhere(    'uc.course IN (:ids)')
            ->andWhere( "up.type=$mtUserPreference" ) // Courses preference
            ->andWhere( "up.value = 1")              // Subscribed to updates
            ->setParameter('ids',$courseIds);
        ;
        $results = $qb->getQuery()->getArrayResult();
        $users = array();
        foreach($results as $r)
        {
            $verified = $r['verified'];
            if(!$verified)
            {
                // Don't send email to verified users
                continue;
            }
            $uid = $r['uid'];
            $cid = $r['cid'];
            $listId = $r['usercourse']['listId'];

            if( !isset($users[$uid]))
            {
                $users[$uid] = array();
            }

            $users[$uid][$listId][] = $cid;
        }

        $output->writeln( "<comment>" . count($users) . ' users found </comment>');

        $scheduled  = 0;
        foreach($users as $uid => $courses)
        {
            $id = $scheduler->schedule(
                new \DateTime( $date ),
                $type,
                'ClassCentral\MOOCTrackerBundle\Job\CourseStartReminderJob',
                $courses,
                $uid
            );

            if($id){
                $scheduled++;
            }
        }
        $output->writeln( "<info>$scheduled jobs scheduled</info>");
    }

} 