<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/9/15
 * Time: 12:18 AM
 */

namespace ClassCentral\MOOCTrackerBundle\Command;


use ClassCentral\MOOCTrackerBundle\Job\ReviewSolicitationJob;
use ClassCentral\SiteBundle\Entity\UserPreference;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReviewSolicitationJobSchedulerCommand extends ContainerAwareCommand {

    protected function configure()
    {
       $this
           ->setName('mooctracker:completedcourses:askforreviews')
           ->setDescription("Ask the user to write reviews for courses they have completed")
           ->addArgument('date', InputArgument::REQUIRED, "Date for which to send the review solicitation emails")
       ;
    }

    /**
     * Builds a list of all the users who have marked the courses as completed, dropped etc
     * one day and creates a job for them
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln( "<comment>Review Solicitation scheduler job started on {$now->format('Y-m-d H:i:s')}</comment>" );

        $scheduler = $this->getContainer()->get('scheduler');

        $date = $input->getArgument('date'); // The date at which the job is to be run
        $dateParts = explode('-', $date);
        if( !checkdate( $dateParts[1], $dateParts[2], $dateParts[0] ) )
        {
            $output->writeLn("<error>Invalid date or format. Correct format is Y-m-d</error>");
            return;
        }

        $users = $this->getUsersWhoHaveTakenACourse( new \DateTime($date) );
        $scheduled = 0;
        foreach ($users as $user)
        {
            $output->writeln( $user->getName() );
            $id = $scheduler->schedule(
                new \DateTime( $date ),
                ReviewSolicitationJob::REVIEW_SOLICITATION_JOB_TYPE,
                'ClassCentral\MOOCTrackerBundle\Job\ReviewSolicitationJob',
                array('date' => $date ),
                $user->getId()
            );


            if($id){
                $scheduled++;
            }
        }

        $output->writeln( "<info>$scheduled jobs scheduled</info>");
    }

    /**
     * Make a list of users who marked courses as completed one day prior to the date mentioned
     * @param \DateTime $dt
     */
    private function getUsersWhoHaveTakenACourse( \DateTime $dt )
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $qb = $em->createQueryBuilder();
        $yesterday = clone $dt;
        $yesterday->sub(new \DateInterval('P1D')); // One Day Ago

        $qb
            ->add('select', 'u')
            ->add('from','ClassCentralSiteBundle:User u')
            ->join('u.userCourses','uc')
            ->join('u.userPreferences', 'up')
            ->andWhere('uc.created > :dt1')
            ->andWhere('uc.created < :dt2')
            ->andWhere('u.isverified = 1')
            ->andWhere( "up.value = 1")
            ->andWhere('uc.listId in (3,4,5,6,7)')
            ->andWhere( "up.type=" . UserPreference::USER_PREFERENCE_REVIEW_SOLICITATION ) // Courses preference
            ->setParameter('dt1', $yesterday->format('Y-m-d'))
            ->setParameter('dt2', $dt->format('Y-m-d'))
        ;

        return $qb->getQuery()->getResult();
    }
} 