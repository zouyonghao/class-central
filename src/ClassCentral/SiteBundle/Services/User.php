<?php

namespace ClassCentral\SiteBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class User {

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function signup(\ClassCentral\SiteBundle\Entity\User $user, $emailVerification = true)
    {
        $em = $this->container->get('doctrine')->getManager();
        $templating = $this->container->get('templating');
        $mailgun = $this->container->get('mailgun');
        $verifyTokenService = $this->container->get('verification_token');

        $user->setEmail(strtolower($user->getEmail())); // Normalize the email
        $password = $user->getPassword();
        $user->setPassword($user->getHashedPassword($password));
        $em->persist($user);
        $em->flush();

        // Login the user
        $token = new UsernamePasswordToken($user, $password,'secured_area',$user->getRoles());
        $this->container->get('security.context')->setToken($token);

        // Send a welcome email but not in the test environment
        if ($this->container->getParameter('kernel.environment') != 'test')
        {
            $html = $templating->renderResponse('ClassCentralSiteBundle:Mail:welcome.html.twig')->getContent();
            $mailgunResponse = $mailgun->sendIntroEmail($user->getEmail(),"'Dhawal Shah'<dhawal@class-central.com>","Welcome to Class Central's MOOC Tracker",$html);

            if($emailVerification)
            {
               // Send an email for verification
                $value = array(
                    'verify' => 1,
                    'email' => $user->getEmail()
                );
                $tokenEntity = $verifyTokenService->create($value,\ClassCentral\SiteBundle\Entity\VerificationToken::EXPIRY_1_YEAR);
                $html = $templating->renderResponse('ClassCentralSiteBundle:Mail:confirm.email.html.twig',array('token' => $tokenEntity->getToken()))->getContent();
                $mailgunResponse = $mailgun->sendSimpleText($user->getEmail(),"no-reply@class-central.com","Please confirm your email",$html);
            }
        }

        return $user;
    }

} 