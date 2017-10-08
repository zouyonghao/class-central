<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 2/14/16
 * Time: 3:30 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Command;


use ClassCentral\MOOCTrackerBundle\Job\NewCoursesEmailJob;
use ClassCentral\SiteBundle\Entity\UserPreference;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCoursesJobSchedulerCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('mooctracker:user:newcourses')
            ->setDescription("Send new courses added in the last month based on users follows")
            ->addArgument('date', InputArgument::REQUIRED, "Date for which the recommendation email has to be sent i.e the job is run.")
            ->addArgument('campaignId',InputArgument::REQUIRED, "Mailgun Campaign id")
            ->addArgument('deliverytime',InputArgument::REQUIRED, "datetime at which email is to be sent(uses local machine timezone) i.e 2015-12-27 21:45:00")
            ->addArgument('split',InputArgument::OPTIONAL,"If the jobs need to be split, the number of splits")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $scheduler = $this->getContainer()->get('scheduler');

        $campaignId = $input->getArgument('campaignId');
        $deliveryTime = new \DateTime($input->getArgument('deliverytime'));
        $split = ($input->getArgument('split')) ? (int)$input->getArgument('split') : 0;

        $date = $input->getArgument('date'); // The date at which the job is to be run
        $dateParts = explode('-', $date);
        if( !checkdate( $dateParts[1], $dateParts[2], $dateParts[0] ) )
        {
            $output->writeLn("<error>Invalid date or format. Correct format is Y-m-d</error>");
            return;
        }

        $now = new \DateTime();
        $output->writeln( "<comment>Scheduling new courses email (based on follows) started on {$now->format('Y-m-d H:i:s')}</comment>" );

        // Get All Users
        $qb = $em->createQueryBuilder();
        $qb
            ->add('select', 'DISTINCT u.id')
            ->add('from','ClassCentralSiteBundle:User u')
            ->join('u.userPreferences', 'up')
            ->join('u.follows','uf')
            ->andWhere('uf.id is NOT NULL')
            ->andWhere( "up.value = 1")
            ->andWhere('u.isverified = 1')
            ->andWhere( "up.type=" . UserPreference::USER_PREFERENCE_PERSONALIZED_COURSE_RECOMMENDATIONS )
        ;

        $users = $qb->getQuery()->getArrayResult();
        $scheduled = 0;
        $dt = new \DateTime( $date );
        $deliveryTime =  $deliveryTime->format(\DateTime::RFC2822);
        foreach($users as $user)
        {
            $id = $scheduler->schedule(
                $dt,
                NewCoursesEmailJob::NEW_COURSES_EMAIL_JOB_TYPE,
                'ClassCentral\MOOCTrackerBundle\Job\NewCoursesEmailJob',
                array(
                    'campaignId' => $campaignId,
                    'deliveryTime' =>$deliveryTime,
                ),
                $user['id'],
                $split
            );

            if($id){
                $scheduled++;
            }
        }

        $output->writeln( "<info>$scheduled new courses emails jobs scheduled</info>");

    }
}