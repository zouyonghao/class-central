<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/22/14
 * Time: 10:40 PM
 */

namespace ClassCentral\SiteBundle\Utility;


use ClassCentral\SiteBundle\Entity\Review;

class ReviewUtility {

    /**
     * Builds an array for the review object that can be serialized
     * @param Review $review
     */
    public static function  getReviewArray(Review $review)
    {
        $rd = new ReadableDate();
        $r = array();
        $r['id'] = $review->getId();
        $r['rating'] = $review->getRating();
        $r['reviewText'] = nl2br( preg_replace("/[\r\n]+/", "\n\n", $review->getReview()));
        $r['hours'] = $review->getHours();
        $r['difficultyId'] = $review->getDifficultyId();
        $r['levelId'] = $review->getLevelId();
        $r['listId'] = $review->getListId();
        $r['created'] = $review->getCreated();
        $r['displayDate'] = $rd->get($review->getCreated()->getTimestamp());
        $r['modified'] = $review->getModified();

        // Review feedback
        $r['fb']['total'] = 0;
        $r['fb']['positive'] = 0;
        $r['fb']['negative'] = 0;
        $fb = $review->getFbSummary();
        if($fb)
        {
            $r['fb']['total'] = $fb->getTotal();
            $r['fb']['positive'] = $fb->getPositive();
            $r['fb']['negative'] = $fb->getNegative();
        }

        $user = $review->getUser();
        $u = array();
        $u['id'] = $user->getId();
        $u['name'] = $user->getDisplayName();

        $r['user'] = $u;

        return $r;
    }
} 