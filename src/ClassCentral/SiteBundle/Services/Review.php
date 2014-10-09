<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/22/14
 * Time: 9:15 PM
 */

namespace ClassCentral\SiteBundle\Services;

use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Utility\ReviewUtility;
use ClassCentral\SiteBundle\Entity\Review as ReviewEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class Review {

    private $container;
    private $cache;
    private $em;

    const AVG_NUM_VOTES = 2.3 ;
    const AVG_RATING = 4.67 ;

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
        $ratingDetails = $this->cache->get(
            $this->getRatingsCacheKey($courseId),
            array($this,'calculateAverageRating'),
            array($courseId)
        );

        return $ratingDetails['rating'];
    }

    /**
     * Calculates the average rating
     * @param $courseId
     * @return array
     */
    public function calculateAverageRating($courseId)
    {
        $course = $this->em->getRepository('ClassCentralSiteBundle:Course')->findOneById($courseId);

        // Basic formula
        $rating = 0;
        $bayesian_average = 0;
        $reviews = $course->getReviews();
        $validReviewsCount = 0;
        if($reviews && $reviews->count() > 0)
        {
            $ratingSum = 0;
            foreach($reviews as $review)
            {
                if($review->getStatus() < ReviewEntity::REVIEW_NOT_SHOWN_STATUS_LOWER_BOUND)
                {
                    $ratingSum += $review->getRating();
                    $validReviewsCount++;
                }
            }

            if($validReviewsCount > 0)
            {
                $rating = $ratingSum/$validReviewsCount;
            }
        }
        return array(
            'rating' => $rating,
            'numRatings' => $validReviewsCount
        );
    }

    /**
     * Calculates the bayseian average rating. This is used for sorting
     * @param $courseId
     * @return float
     */
    public function getBayesianAverageRating( $courseId )
    {
        $ratingDetails = $this->cache->get(
            $this->getRatingsCacheKey($courseId),
            array($this,'calculateAverageRating'),
            array($courseId)
        );

        $bayesian_average = 0;
        $rating = $ratingDetails['rating'];
        $numRatings = $ratingDetails['numRatings'];

        if( $rating > 0 )
        {
            $bayesian_average = ((self::AVG_NUM_VOTES * self::AVG_RATING) + ($numRatings * $rating)) / (self::AVG_NUM_VOTES + $numRatings);
        }

        return round( $bayesian_average, 4);
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

        $reviewEntities = $this->em->createQuery("
               SELECT r,f, LENGTH (r.review) as reviewLength from ClassCentralSiteBundle:Review r JOIN r.course c LEFT JOIN r.fbSummary f WHERE c.id = $courseId
                ORDER BY f.positive DESC, reviewLength DESC")
            ->getResult();
        $r = array();
        $reviewCount = 0;
        $ratingCount = 0;
        foreach($reviewEntities as $review)
        {
            $review = $review[0];
            if($review->getStatus() < ReviewEntity::REVIEW_NOT_SHOWN_STATUS_LOWER_BOUND )
            {
                $ratingCount++;

                if( !$review->getIsRating() )
                {
                    // Hide the review table entries that are purely rating
                    $r[] = ReviewUtility::getReviewArray($review);
                    $reviewCount++;
                }
            }
        }

        $reviews = array();
        $reviews['count'] = $ratingCount;
        $reviews['ratingCount'] = $ratingCount;
        $reviews['reviewCount'] = $reviewCount;
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

    public function getAverageRatingsCacheKey($courseId)
    {
        return "MOOC_AVERAGE_RATINGS_" . $courseId;
    }

    public function clearCache($courseId)
    {
        $this->cache->deleteCache($this->getReviewsCacheKey($courseId));
        $this->cache->deleteCache($this->getRatingsCacheKey($courseId));
    }

    /**
     * Creates/Updates a review
     * @param $courseId
     * @param $reviewData
     */
    public function saveReview($courseId, \ClassCentral\SiteBundle\Entity\User $user, $reviewData, $isAdmin = false)
    {
        $em = $this->em;
        $newReview = false;

        $course = $em->getRepository('ClassCentralSiteBundle:Course')->find($courseId);
        if (!$course) {
            return $this->getAjaxResponse(false,'Course not found');
        }

        // Get the review object if it exists
        $review = null;
        if(isset($reviewData['reviewId']) && is_numeric($reviewData['reviewId']))
        {
            // Its an edit. Get the review
            // Get the review
            $review = $em->getRepository('ClassCentralSiteBundle:Review')->find($reviewData['reviewId']);
            if(!$review)
            {
                return $this->getAjaxResponse(false, 'Review does not exist');
            }

            // Check if the user has access to edit the review
            // Either the user is an admin or the person who created the review
            $admin =  $this->container->get('security.context')->isGranted('ROLE_ADMIN');
            if(!$admin && $user->getId() != $review->getUser()->getId())
            {
                return $this->getAjaxResponse(false, 'User does not have access to edit the review');
            }

        } else
        {
            $newReview = true;
            $review = $em->getRepository('ClassCentralSiteBundle:Review')->findOneBy(array(
                'user' => $user,
                'course' => $course
            ));

            // Admins can create multiple reviews - for adding external reviews
            if($review && !$isAdmin)
            {
                return $this->getAjaxResponse(false, 'Review already exists');
            }

            $review = new \ClassCentral\SiteBundle\Entity\Review();
            $review->setUser($user);
            $review->setCourse($course);
        }

        // Get the offering
        if(isset($reviewData['offeringId']) && $reviewData['offeringId'] != -1)
        {
            $offering = $em->getRepository('ClassCentralSiteBundle:Offering')->find($reviewData['offeringId']);
            $review->setOffering($offering);
        }

        // check if the rating valid
        if(!isset($reviewData['rating']) &&  !is_numeric($reviewData['rating']))
        {
            return $this->getAjaxResponse(false,'Rating is required and expected to be a number');
        }
        // Check if the rating is in range
        if(!($reviewData['rating'] >= 1 && $reviewData['rating'] <= 5))
        {
            return $this->getAjaxResponse(false,'Rating should be between 1 to 5');
        }


        // If review exists its length should be atleast 20 words
        if(!empty($reviewData['reviewText']) && str_word_count($reviewData['reviewText']) < 20)
        {
            return $this->getAjaxResponse(false,'Review should be at least 20 words long');
        }

        $review->setRating($reviewData['rating']);
        $review->setReview($reviewData['reviewText']);


        // Progress is required
        if(!isset($reviewData['progress']))
        {
            return $this->getAjaxResponse(false,'Progress is required');
        }
        // Progress
        if(isset($reviewData['progress']) && array_key_exists($reviewData['progress'], UserCourse::$progress))
        {
            $review->setListId($reviewData['progress']);

            // Add/update the course to users library
            if(!$isAdmin)
            {
                // Do not add this t
                $userService = $this->container->get('user_service');
                $uc = $userService->addCourse($user, $course, $reviewData['progress']);
            }
        }

        // Difficulty
        if(isset($reviewData['difficulty']) && array_key_exists($reviewData['difficulty'], \ClassCentral\SiteBundle\Entity\Review::$difficulty))
        {
            $review->setDifficultyId($reviewData['difficulty']);
        }

        // Level
        if(isset($reviewData['level']) && array_key_exists($reviewData['level'], \ClassCentral\SiteBundle\Entity\Review::$levels))
        {
            $review->setLevelId($reviewData['level']);
        }

        // Effort
        if(isset($reviewData['effort']) && is_numeric($reviewData['effort']) && $reviewData['effort'] > 0)
        {
            $review->setHours($reviewData['effort']);
        }

        if($isAdmin)
        {
            // Status
            if(isset($reviewData['status']) && array_key_exists($reviewData['status'],\ClassCentral\SiteBundle\Entity\Review::$statuses))
            {
                $review->setStatus($reviewData['status']);
            }

            // External reviewer name
            if(isset($reviewData['externalReviewerName']) )
            {
                $review->setReviewerName($reviewData['externalReviewerName']);
            }

            // External review link
            if(isset($reviewData['externalReviewLink']) && filter_var($reviewData['externalReviewLink'], FILTER_VALIDATE_URL))
            {
                $review->setExternalLink( $reviewData['externalReviewLink'] );
            }

        }

        $user->addReview($review);
        $em->persist($review);
        $em->flush();

        $this->clearCache($courseId);

        // If its an actual user and not an anonymous user update the session information
        if($user->getEmail() != \ClassCentral\SiteBundle\Entity\User::REVIEW_USER_EMAIL)
        {
            //Update the users review history in session
            $userSession = $this->container->get('user_session');
            $userSession->saveUserInformationInSession();

            if($newReview)
            {
                $userSession->notifyUser(
                    UserSession::FLASH_TYPE_SUCCESS,
                    'Review created',
                    sprintf("Review for <i>%s</i> created successfully", $review->getCourse()->getName())
                );
            }
            else
            {
                $userSession->notifyUser(
                    UserSession::FLASH_TYPE_SUCCESS,
                    'Review updated',
                    sprintf("Your review for <i>%s</i> has been updated successfully", $review->getCourse()->getName())
                );
            }
        }
        return $review;
    }

    private function getAjaxResponse($success = false, $message = '')
    {
        $response = array('success' => $success, 'message' => $message);
        return json_encode($response);
    }


} 