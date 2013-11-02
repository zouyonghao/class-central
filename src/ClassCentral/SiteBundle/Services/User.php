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

    public function signup(\ClassCentral\SiteBundle\Entity\User $user)
    {
        $em = $this->container->get('doctrine')->getManager();
        $templating = $this->container->get('templating');
        $mailgun = $this->container->get('mailgun');

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
        }

        return $user;
    }
} 