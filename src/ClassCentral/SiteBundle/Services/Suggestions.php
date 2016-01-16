<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/15/16
 * Time: 4:08 PM
 */

namespace ClassCentral\SiteBundle\Services;


use ClassCentral\SiteBundle\Entity\Item;
use ClassCentral\SiteBundle\Entity\User as UserEntity;
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
    public function newCoursesbyUser(UserEntity $user, $startDate, $endDate)
    {
        // Get follows

    }

    /**
     * Find all the course recommendations based on user's follows between the two dates
     * @param User $user
     * @param $startDate
     * @param $endDate
     */
    public function suggestionsByUser(UserEntity $user, $startDate, $endDate)
    {

    }

    /**
     * Gets the recommended courses for the personalized recommendations
     * page for the user
     * @param User $user
     * @param $params
     */
    public function getRecommendations(UserEntity $user, $params)
    {
        $cl = $this->container->get('course_listing');

        $follows = $user->getFollowsCategorizedByItem();

        $institutionIds = $follows[Item::ITEM_TYPE_INSTITUTION];
        $providerIds = $follows[Item::ITEM_TYPE_PROVIDER];
        $subjectIds = $follows[Item::ITEM_TYPE_SUBJECT];
        $must = array(
            'terms' => array(
                'subjects.id' => $subjectIds
            ));

        $data = $cl->byFollows($institutionIds,$subjectIds, $providerIds, $params, $must);

        return $data;
    }

}