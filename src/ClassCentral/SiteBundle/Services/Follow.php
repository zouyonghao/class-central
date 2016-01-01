<?php

/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 12/31/15
 * Time: 5:45 PM
 */

namespace ClassCentral\SiteBundle\Services;

use ClassCentral\SiteBundle\Entity\Item;
use ClassCentral\SiteBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Follow
{

    private $container;
    private $em;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
    }

    public function follow( User $user, $obj)
    {
        // Check if user is already is following
        $follow = $this->getFollow($user, $obj);
        if( $follow )
        {
            return $follow;
        }

        $item = Item::getItemFromObject($obj);
        $follow = new \ClassCentral\SiteBundle\Entity\Follow();
        $follow->setItem( $item->getType() );
        $follow->setItemId( $item->getId() );
        $follow->setUser($user);

        $this->em->persist($follow);
        $this->em->flush();

        return $follow;
    }


    public function getFollow(User $user, $obj)
    {
        $item = Item::getItemFromObject($obj);
        $follow = $this->em->getRepository('ClassCentralSiteBundle:Follow')->findOneBy( array(
            'item' => $item->getType(),
            'itemId' => $item->getId(),
            'user' => $user
        ) );

        return $follow;
    }
}