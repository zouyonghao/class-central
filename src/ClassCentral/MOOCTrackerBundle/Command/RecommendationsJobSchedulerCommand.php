<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/27/16
 * Time: 1:54 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Command;


use ClassCentral\MOOCTrackerBundle\Job\RecommendationEmailJob;
use ClassCentral\SiteBundle\Entity\UserPreference;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecommendationsJobSchedulerCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('mooctracker:user:recommendations')
            ->setDescription("Send recommendations email to the user")
            ->addArgument('date', InputArgument::REQUIRED, "Date for which the recommendation email has to be sent i.e the job is run")
            ->addArgument('campaignId',InputArgument::REQUIRED, "Mailgun Campaign id")
            ->addArgument('deliverytime',InputArgument::REQUIRED, "datetime at which email is to be sent(uses local machine timezone) i.e 2015-12-27 21:45:00")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $scheduler = $this->getContainer()->get('scheduler');

        $campaignId = $input->getArgument('campaignId');
        $deliveryTime = new \DateTime($input->getArgument('deliverytime'));

        $date = $input->getArgument('date'); // The date at which the job is to be run
        $dateParts = explode('-', $date);
        if( !checkdate( $dateParts[1], $dateParts[2], $dateParts[0] ) )
        {
            $output->writeLn("<error>Invalid date or format. Correct format is Y-m-d</error>");
            return;
        }

        $now = new \DateTime();
        $output->writeln( "<comment>Scheduling recommendations email started on {$now->format('Y-m-d H:i:s')}</comment>" );

        // Get All Users
        $qb = $em->createQueryBuilder();
        $qb
            ->add('select', 'DISTINCT u.id')
            ->add('from','ClassCentralSiteBundle:User u')
            ->join('u.userPreferences', 'up')
            ->join('u.follows','uf')
            ->andWhere('uf is NOT NULL')
            ->andWhere( "up.value = 1")
            ->andWhere( "up.type=" . UserPreference::USER_PREFERENCE_FOLLOW_UP_EMAILs )
        ;

        $users = $qb->getQuery()->getArrayResult();
        $scheduled = 0;
        $dt = new \DateTime( $date );
        $deliveryTime =  $deliveryTime->format(\DateTime::RFC2822);
        foreach($users as $user)
        {
            $id = $scheduler->schedule(
                $dt,
                RecommendationEmailJob::RECOMMENDATION_EMAIL_JOB_TYPE,
                'ClassCentral\MOOCTrackerBundle\Job\AnnouncementEmailJob',
                array(
                    'campaignId' => $campaignId,
                    'deliveryTime' =>$deliveryTime
                ),
                $user['id']
            );

            if($id){
                $scheduled++;
            }
        }

        $output->writeln( "<info>$scheduled recommendation emails jobs scheduled</info>");
    }
}