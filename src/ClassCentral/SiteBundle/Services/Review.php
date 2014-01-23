<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/22/14
 * Time: 9:15 PM
 */

namespace ClassCentral\SiteBundle\Services;

use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Utility\ReviewUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Review {

    private $container;
    private $cache;
    private $em;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->cache = $container->get('cache');
        $this->em = $container->get('doctrine')->getManager();
    }

    /**
     * Calculate ratings for a particular course
     * @param Course $course
     */
    public function getRatings($courseId)
    {
        return $this->cache->get(
            $this->getRatingsCacheKey($courseId),
            array($this,'calculateRatings'),
            array($courseId)
        );
    }

    public function calculateRatings($courseId)
    {
        $course = $this->em->getRepository('ClassCentralSiteBundle:Course')->findOneById($courseId);

        // Basic formula
        $rating = 0;
        $reviews = $course->getReviews();

        if($reviews->count() > 0)
        {
            $ratingSum = 0;
            foreach($reviews as $review)
            {
                $ratingSum += $review->getRating();
            }

            $rating = $ratingSum/$reviews->count();
        }

        return $rating;
    }

    public function getReviews($courseId)
    {
        return $this->cache->get(
            $this->getReviewsCacheKey($courseId),
            array($this,'getReviewsArray'),
            array($courseId)
        );
    }

    public function getReviewsArray($courseId)
    {
        $course = $this->em->getRepository('ClassCentralSiteBundle:Course')->findOneById($courseId);

        $r = array();
        $reviewCount = 0;
        foreach($course->getReviews() as $review)
        {
            $r[] = ReviewUtility::getReviewArray($review);
            $reviewText = $review->getReview();
            if(!empty($reviewText))
            {
                $reviewCount++;
            }
        }

        $reviews = array();
        $reviews['count'] = $reviewCount;
        $reviews['reviews'] = $r;

        return $reviews;
    }

    public function getReviewsCacheKey($courseId)
    {
        return "MOOC_REVIEWS_" . $courseId;
    }

    public function getRatingsCacheKey($courseId)
    {
        return "MOOC_RATINGS_" . $courseId;
    }

    public function clearCache($courseId)
    {
        $this->cache->deleteCache($this->getReviewsCacheKey($courseId));
        $this->cache->deleteCache($this->getRatingsCacheKey($courseId));
    }
} 