<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/4/14
 * Time: 12:54 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Command;


use ClassCentral\MOOCTrackerBundle\Job\SearchTermJob;
use ClassCentral\SiteBundle\Entity\UserPreference;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SearchNotificationJobSchedulerCommand extends ContainerAwareCommand {

    public function  configure()
    {
        $this
            ->setName('mooctracker:notification:search')
            ->setDescription('Generate jobs for search alerts')
            ->addArgument('type', InputArgument::REQUIRED, "mt_search_new_courses/mt_search_recent_courses")
            ->addArgument("date", InputArgument::REQUIRED, "Date for which the jobs should be generated eg. Y-m-d")
            ->addArgument('split',InputArgument::OPTIONAL,"If the jobs need to be split, the number of splits")
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln( "<comment>Search notifications scheduler started on {$now->format('Y-m-d H:i:s')}</comment>");

        $esCourses = $this->getContainer()->get('es_courses');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $scheduler = $this->getContainer()->get('scheduler');

        // Parse the input arguments
        $type = $input->getArgument('type');
        $date = $input->getArgument('date'); // The date at which the job is to be run
        $split = ($input->getArgument('split')) ? (int)$input->getArgument('split') : 0;
        if($type != SearchTermJob::JOB_TYPE_NEW_COURSES && $type != SearchTermJob::JOB_TYPE_RECENT_COURSES)
        {
            // Invalid job type
            $output->writeln("<error>Invalid job type. Valid types are mt_search_new_courses/mt_search_recent_courses</error>");
            return;
        }
        $dateParts = explode('-', $date);
        if( !checkdate( $dateParts[1], $dateParts[2], $dateParts[0] ) )
        {
            $output->writeLn("<error>Invalid date or format. Correct format is Y-m-d</error>");
            return;
        }

        // Get a list of all users with search terms
        $qb = $em->createQueryBuilder();
        $mtUserPreference = UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES;
        $qb
            ->add('select','u')
            ->add('from', 'ClassCentralSiteBundle:User u')
            ->join('u.moocTrackerSearchTerms','um')
            ->join('u.userPreferences', 'up')
            ->andWhere('um is NOT NULL')
            ->andWhere('u.isverified = 1')
            ->andWhere( "up.type=$mtUserPreference" ) // Courses preference
            ->andWhere( "up.value = 1")              // Subscribed to updates
        ;

        $users = $qb->getQuery()->getResult();

        $scheduled  = 0;
        foreach($users as $user)
        {
            $id = $scheduler->schedule(
              new \DateTime( $date ),
              $type,
              'ClassCentral\MOOCTrackerBundle\Job\SearchTermJob',
               array('userId' => $user->getId() ),
               $user->getId(),
                $split
            );


            if($id){
                $scheduled++;
            }
        }

        $output->writeln( "<info>$scheduled jobs scheduled</info>");
    }
} 