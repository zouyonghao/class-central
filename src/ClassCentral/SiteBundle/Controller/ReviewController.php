<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/19/14
 * Time: 4:05 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Entity\Review;
use ClassCentral\SiteBundle\Entity\UserCourse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReviewController extends Controller {

    /**
     * Renders the form to create a new review
     * @param Request $request
     * @param $courseId
     */
    public function newAction(Request $request, $courseId) {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        // Get the course
        $course = $em->getRepository('ClassCentralSiteBundle:Course')->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }
        $offerings = $em->getRepository('ClassCentralSiteBundle:Offering')->findAllByCourseIds(array($courseId));
        $offeringTypesOrder = array('upcoming','ongoing','selfpaced','past');
        $offeringCount = 0;
        $offering = null; // If there is only one offering this will keep track of it
        // offering count
        foreach($offerings as $type => $ot) {
            if(in_array($type,$offeringTypesOrder))
            {
                foreach($ot as $o) {
                    $offering = $o;
                    $offeringCount++;
                }
            }
        }

        $review = $em->getRepository('ClassCentralSiteBundle:Review')->findOneBy(array(
            'user' => $user,
            'course' => $course
        ));

        if($review)
        {
            // TODO: Redirect the user to the review
            return null;
        }

        return $this->render('ClassCentralSiteBundle:Review:new.html.twig', array(
            'page' => 'write_review',
            'progress' => UserCourse::$progress,
            'difficulty'=> Review::$difficulty,
            'course' => $course,
            'levels' => Review::$levels,
            'offerings' => $offerings,
            'offeringTypes' => Offering::$types,
            'offeringCount' => $offeringCount,
            'offering' => $offering,
            'offeringTypesOrder' => $offeringTypesOrder,
        ));
    }

    /**
     * Validates and creates the review
     * @param Request $request
     * @param $courseId
     */
    public function createAction(Request $request, $courseId) {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $logger = $this->get('logger');
        $ru = $this->get('review');
        $userSession = $this->get('user_session');


        $course = $em->getRepository('ClassCentralSiteBundle:Course')->find($courseId);
        if (!$course) {
            return $this->getAjaxResponse(false,'Course not found');
        }


        $review = $em->getRepository('ClassCentralSiteBundle:Review')->findOneBy(array(
                'user' => $user,
                'course' => $course
        ));
        if($review) {
            return $this->getAjaxResponse(false,'Review already exists');
        }

        $review = new Review();
        $review->setUser($user);
        $review->setCourse($course);


        $content = $this->getRequest("request")->getContent();
        if(empty($content)) {
            return $this->getAjaxResponse(false);
        }

        // Validate the response
        $reviewData = json_decode($content, true);

        // Get the offering
        if($reviewData['offeringId'] != -1)
        {
            $offering = $em->getRepository('ClassCentralSiteBundle:Offering')->find($reviewData['offeringId']);
            $review->setOffering($offering);
        }

        // check if the rating valid
        if(!isset($reviewData['rating']) &&  !is_numeric($reviewData['rating']))
        {
            $this->getAjaxResponse(false,'Rating is required and expected to be a number');
        }
        // Check if the rating is in range
        if(!($reviewData['rating'] >= 1 && $reviewData['rating'] <= 5))
        {
            $this->getAjaxResponse(false,'Rating should be between 1 to 5');
        }

        // If review exists its length should be atleast 20 words
        if(!empty($reviewData['reviewText']) && str_word_count($reviewData['reviewText']) < 20)
        {
            $this->getAjaxResponse(false,'Review should be at least 20 words long');
        }

        $review->setRating($reviewData['rating']);
        $review->setReview($reviewData['reviewText']);

        // Progress
        if(isset($reviewData['progress']) && array_key_exists($reviewData['progress'], UserCourse::$progress))
        {
            $review->setListId($reviewData['progress']);
        }

        // Difficulty
        if(isset($reviewData['difficulty']) && array_key_exists($reviewData['difficulty'], Review::$difficulty))
        {
            $review->setDifficultyId($reviewData['difficulty']);
        }

        // Level
        if(isset($reviewData['level']) && array_key_exists($reviewData['level'], Review::$levels))
        {
            $review->setLevelId($reviewData['level']);
        }

        // Effort
        if(isset($reviewData['effort']) && is_numeric($reviewData['effort']) && $reviewData['effort'] > 0)
        {
            $review->setHours($reviewData['effort']);
        }

        $em->persist($review);
        $em->flush();

        // clear the review cache for this particular course
        $ru->clearCache($course->getId());
        // Update the users review history in session
        $userSession->saveReviewInformationInSession();
        return $this->getAjaxResponse(true);
    }

    private function getAjaxResponse($success = false, $message = '')
    {
        $response = array('success' => $success, 'message' => $message);
        return new Response(json_encode($response));
    }
} 