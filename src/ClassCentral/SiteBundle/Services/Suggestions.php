<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/15/16
 * Time: 4:08 PM
 */

namespace ClassCentral\SiteBundle\Services;


use ClassCentral\SiteBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Suggestions
{

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
    }

    /**
     * Find all the new courses based on user's follows added between the two dates
     * @param User $user
     * @param $startDate
     * @param $endDate
     */
    public function newCoursesbyUser(User $user, $startDate, $endDate)
    {

    }

    /**
     * Find all the course recommendations based on user's follows between the two dates
     * @param User $user
     * @param $startDate
     * @param $endDate
     */
    public function suggestionsByUser(User $user, $startDate, $endDate)
    {

    }

}