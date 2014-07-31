<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 7/30/14
 * Time: 5:37 PM
 */

namespace ClassCentral\MOOCTrackerBundle;


use ClassCentral\SiteBundle\Entity\UserPreference;

class MTHelper {

    public static function getUsersToCoursesMap( $em,$courseIds )
    {
        // Get a list of all users and build a user to courses map
        $qb = $em->createQueryBuilder();
        $mtUserPreference = UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES;
        $qb->add('select', 'uc as usercourse, c.id as cid,u.id as uid, u.isverified as verified')
            ->add('from', 'ClassCentralSiteBundle:UserCourse uc')
            ->join('uc.course', 'c')
            ->join('uc.user', 'u')
            ->join('u.userPreferences', 'up')
            ->andWhere(    'uc.course IN (:ids)')
            ->andWhere( "up.type=$mtUserPreference" ) // Courses preference
            ->andWhere( "up.value = 1")              // Subscribed to updates
            ->setParameter('ids',$courseIds);
        ;
        $results = $qb->getQuery()->getArrayResult();
        $users = array();
        foreach($results as $r)
        {
            $verified = $r['verified'];
            if(!$verified)
            {
                // Don't send email to verified users
                continue;
            }
            $uid = $r['uid'];
            $cid = $r['cid'];
            $listId = $r['usercourse']['listId'];

            if( !isset($users[$uid]))
            {
                $users[$uid] = array();
            }

            $users[$uid][$listId][] = $cid;
        }

        return $users;
    }
} 