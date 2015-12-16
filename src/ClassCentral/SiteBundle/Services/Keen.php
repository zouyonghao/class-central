<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 12/16/15
 * Time: 2:15 PM
 */

namespace ClassCentral\SiteBundle\Services;


use ClassCentral\SiteBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Keen
{
    private $container;
    private $keenClient;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->keenClient = $container->get('keen_io');
    }

    public function recordLogins(User $user, $type)
    {
        $this->keenClient->addEvent('logins', array(
            'user_id' => $user->getId(),
            'type' => $type
        ));
    }
}