<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 12/25/15
 * Time: 9:12 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Command;


use ClassCentral\MOOCTrackerBundle\Job\AnnouncementEmailJob;
use ClassCentral\SiteBundle\Entity\UserPreference;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Job to schedule announcement emails to everyone. Due to how the scheduler checks for duplicate jobs
 * maximum of one annoucement emails a day
 * Class AnnouncementsJobSchedulerCommand
 * @package ClassCentral\MOOCTrackerBundle\Command
 */
class AnnouncementsJobSchedulerCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('mooctracker:user:announcement')
            ->setDescription("Send an announcement email to the user")
            ->addArgument('date', InputArgument::REQUIRED, "Date for which the announcement email has to be sent")
            ->addArgument('subject',InputArgument::REQUIRED,"Email Subject")
            ->addArgument('template',InputArgument::REQUIRED, "Complete filename of the inlined template to send")
            ->addArgument('campaignId',InputArgument::REQUIRED, "Mailgun Campaign id")
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $scheduler = $this->getContainer()->get('scheduler');

        $subject = $input->getArgument('subject');
        $template = $input->getArgument('template');
        $campaignId = $input->getArgument('campaignId');
        $date = $input->getArgument('date'); // The date at which the job is to be run
        $dateParts = explode('-', $date);
        if( !checkdate( $dateParts[1], $dateParts[2], $dateParts[0] ) )
        {
            $output->writeLn("<error>Invalid date or format. Correct format is Y-m-d</error>");
            return;
        }

        $now = new \DateTime();
        $output->writeln( "<comment>Scheduling announcement email started on {$now->format('Y-m-d H:i:s')}</comment>" );

        // Get All Users
        $qb = $em->createQueryBuilder();
        $qb
            ->add('select', 'u.id')
            ->add('from','ClassCentralSiteBundle:User u')
            ->join('u.userPreferences', 'up')
            ->andWhere( "up.value = 1")
            ->andWhere( "up.type=" . UserPreference::USER_PREFERENCE_FOLLOW_UP_EMAILs )
        ;

        $users = $qb->getQuery()->getArrayResult();
        $scheduled = 0;
        foreach($users as $user)
        {
            $id = $scheduler->schedule(
                new \DateTime( $date ),
                AnnouncementEmailJob::ANNOUNCEMENT_EMAIL_JOB_TYPE,
                'ClassCentral\MOOCTrackerBundle\Job\AnnouncementEmailJob',
                array(
                    'template' => $template,
                    'subject'=> $subject,
                    'campaignId' => $campaignId
                ),
                $user['id']
            );

            if($id){
                $scheduled++;
            }

        }

        $output->writeln( "<info>$scheduled jobs scheduled</info>");
    }

}