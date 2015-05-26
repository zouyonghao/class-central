<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/25/15
 * Time: 9:35 PM
 */

namespace ClassCentral\SiteBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateProfileScoreCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('classcentral:user:profilescore')
            ->setDescription('Updates the profile score for all users')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $users = $em->getRepository('ClassCentralSiteBundle:User')->findAll();
        $userService = $this->getContainer()->get('user_service');
        $profilesUpdated = 0;
        foreach($users as $user)
        {
            $profile = $user->getProfile();
            if( !$profile )
            {
                continue;
            }
            $score = $userService->calculateProfileScore($user);
            if ( $score != $profile->getScore() )
            {
                $profile->setScore( $score );
                $em->persist($profile);
                $profilesUpdated++;
            }
        }
        $em->flush();
        $output->writeln("Profiles Updated : " . $profilesUpdated);
    }
} 