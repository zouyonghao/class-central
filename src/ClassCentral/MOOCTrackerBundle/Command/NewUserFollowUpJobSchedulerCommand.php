<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/12/15
 * Time: 7:11 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Command;


use ClassCentral\MOOCTrackerBundle\Job\NewUserFollowUpJob;
use ClassCentral\SiteBundle\Entity\UserPreference;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends e follow up emails to users whose account are two days old
 * Class NewUserFollowUpJobSchedulerCommand
 * @package ClassCentral\MOOCTrackerBundle\Command
 */
class NewUserFollowUpJobSchedulerCommand extends ContainerAwareCommand {


    protected function configure()
    {
        $this
            ->setName('mooctracker:user:followup')
            ->setDescription("Ask the user to write reviews for courses they have completed")
            ->addArgument('date', InputArgument::REQUIRED, "Date for which the user the emails are to be sent")
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $now = new \DateTime();
        $output->writeln( "<comment>New User follow up emails scheduler job started on {$now->format('Y-m-d H:i:s')}</comment>" );

        $scheduler = $this->getContainer()->get('scheduler');

        $date = $input->getArgument('date'); // The date at which the job is to be run
        $dateParts = explode('-', $date);
        if( !checkdate( $dateParts[1], $dateParts[2], $dateParts[0] ) )
        {
            $output->writeLn("<error>Invalid date or format. Correct format is Y-m-d</error>");
            return;
        }

        $users = $this->getAllUsers( new \DateTime($date) );
        $scheduled = 0;

        foreach ($users as $user)
        {
            $output->writeln( $user->getName() );
            $id = $scheduler->schedule(
                new \DateTime( $date ),
                NewUserFollowUpJob::NEW_USER_FOLLOW_UP_JOB_TYPE,
                'ClassCentral\MOOCTrackerBundle\Job\NewUserFollowUpJob',
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
     * Get all users with account created two days ago
     * @param \DateTime $dt
     */
    private function getAllUsers( \DateTime $dt )
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $qb = $em->createQueryBuilder();

        $yesterday = clone $dt;
        $yesterday->sub(new \DateInterval('P1D')); // One Day Ago

        $twoDaysAgo =  clone $dt;
        $twoDaysAgo->sub(new \DateInterval('P2D')); // One Day Ago

        $qb
            ->add('select', 'u')
            ->add('from','ClassCentralSiteBundle:User u')
            ->join('u.userPreferences', 'up')
            ->andWhere('u.created > :dt1')
            ->andWhere('u.created < :dt2')
            ->andWhere( "up.value = 1")
            ->andWhere( "up.type=" . UserPreference::USER_PREFERENCE_FOLLOW_UP_EMAILs ) // Courses preference
            ->setParameter('dt1', $twoDaysAgo->format('Y-m-d'))
            ->setParameter('dt2', $yesterday->format('Y-m-d'))
        ;

        return $qb->getQuery()->getResult();
    }
} 