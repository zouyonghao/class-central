<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 10/18/14
 * Time: 9:46 PM
 */

namespace ClassCentral\SiteBundle\Command;


use ClassCentral\SiteBundle\Entity\UserPreference;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteUserCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName("classcentral:deleteuser")
            ->setDescription("Given a user id, deletes it")
            ->addArgument('uid', InputArgument::REQUIRED,"Which user id or email address? i.e 1 or name@example.com");

    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $userService = $this->getContainer()->get('user_service');

        $uid = $input->getArgument('uid');
        if($uid == 'all')
        {
            $userPreferences = $em->getRepository('ClassCentralSiteBundle:UserPreference')->findBy(array(
                'type' => UserPreference::USER_PROFILE_DELETE_ACCOUNT
            ));
            $output->writeln(count($userPreferences) . 'found');
            foreach($userPreferences as $userPreference)
            {
                $user = $userPreference->getUser();
                $userEmail = $user->getEmail();
                $output->writeln( "Deleting user {$user->getId()} with name " . $user->getDisplayName() );
                $userService->deleteUser($user);
                $userService->sendDeleteEmail($userEmail);
            }
        }
        else
        {
            if(is_numeric($uid))
            {
                $user = $em->getRepository('ClassCentralSiteBundle:User')->find( $uid );
            }
            else
            {
                $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneByEmail( $uid );
            }

            if( $user )
            {
                $userId = $user->getId();
                $userEmail = $user->getEmail();
                $output->writeln( "Deleting user with name " . $user->getDisplayName() . " ($userId)" );
                // Delete the user
                $userService->deleteUser($user);
                $output->writeLn("User $userId  deleted");
                $userService->sendDeleteEmail($userEmail);

            }
            else
            {
                $output->writeln("User $uid does not exist");
            }
        }
    }

} 