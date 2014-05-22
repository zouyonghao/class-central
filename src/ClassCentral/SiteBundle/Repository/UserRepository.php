<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/22/14
 * Time: 1:20 AM
 */

namespace ClassCentral\SiteBundle\Repository;


use ClassCentral\SiteBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository {

    public function getReviewUser()
    {
        return $this->findOneBy( array('email' => User::REVIEW_USER_EMAIL) );
    }
} 