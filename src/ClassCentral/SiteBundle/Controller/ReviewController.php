<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/19/14
 * Time: 4:05 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Entity\Review;
use ClassCentral\SiteBundle\Entity\ReviewFeedback;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Utility\ReviewUtility;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReviewController extends Controller {

    /**
     * Returns an array with fields required to render
     * create/edit review form
     * @param Course $course
     */
    private function getReviewFormData(Course $course)
    {
        $em = $this->getDoctrine()->getManager();
        $offerings = $em->getRepository('ClassCentralSiteBundle:Offering')->findAllByCourseIds(array($course->getId()));
        $offeringTypesOrder = array('ongoing','selfpaced','past');
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

        return array(
            'progress' => UserCourse::$progress,
            'difficulty'=> Review::$difficulty,
            'course' => $course,
            'levels' => Review::$levels,
            'offerings' => $offerings,
            'offeringTypes' => Offering::$types,
            'offeringCount' => $offeringCount,
            'offering' => $offering,
            'offeringTypesOrder' => $offeringTypesOrder,
            'reviewStatuses' => Review::$statuses,
            'showStatus' => false
        );
    }


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

        $formData = $this->getReviewFormData($course);

        $review = $em->getRepository('ClassCentralSiteBundle:Review')->findOneBy(array(
            'user' => $user,
            'course' => $course
        ));

        if($review)
        {
            // TODO: Redirect the user to the review
            return null;
        }

        $formData['page'] = 'write_review';
        $formData['review'] = new Review(); // Empty review object
        return $this->render('ClassCentralSiteBundle:Review:new.html.twig', $formData);
    }

    /**
     * Renders the edit form
     * @param Request $request
     * @param $reviewId
     */
    public function editAction(Request $request, $reviewId)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $review = $em->getRepository('ClassCentralSiteBundle:Review')->find($reviewId);
        if(!$review)
        {
            // Show an error page
            return null;
        }

        // Either the user is an admin or the person who created the review
        $admin =  $this->get('security.context')->isGranted('ROLE_ADMIN');
        if(!$admin && $user->getId() != $review->getUser()->getId())
        {
            return "You do not have access to this page";
        }

        $formData = $this->getReviewFormData($review->getCourse());
        $formData['page'] = 'edit_review';
        $formData['review'] = $review;
        $formData['showStatus'] = $admin;
        return $this->render('ClassCentralSiteBundle:Review:new.html.twig', $formData);
    }

    /**
     * Validates and creates the review. Updates if the review already
     * is created
     * @param Request $request
     * @param $courseId
     */
    public function createAction(Request $request, $courseId) {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $logger = $this->get('logger');
        $ru = $this->get('review');
        $userSession = $this->get('user_session');
        $newReview = false;

        $course = $em->getRepository('ClassCentralSiteBundle:Course')->find($courseId);
        if (!$course) {
            return $this->getAjaxResponse(false,'Course not found');
        }

        // Get the json post data
        $content = $this->getRequest("request")->getContent();
        if(empty($content)) {
            return $this->getAjaxResponse(false, "Error retrieving form details");
        }
        $reviewData = json_decode($content, true);

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
            $admin =  $this->get('security.context')->isGranted('ROLE_ADMIN');
            if(!$admin && $user->getId() != $review->getUser()->getId())
            {
                return $this->getAjaxResponse(false, 'User does not have access to edit the review');
            }

        } else
        {
            $review = $em->getRepository('ClassCentralSiteBundle:Review')->findOneBy(array(
                    'user' => $user,
                    'course' => $course
            ));

            if($review)
            {
                return $this->getAjaxResponse(false, 'Review already exists');
            }

            $review = new Review();
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
            $userService = $this->get('user_service');
            $uc = $userService->addCourse($user, $course, $reviewData['progress']);
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

        // Status
        if(isset($reviewData['status']) && array_key_exists($reviewData['status'],Review::$statuses))
        {
            $review->setStatus($reviewData['status']);
        }

        $em->persist($review);
        $em->flush();

        // clear the review cache for this particular course
        $ru->clearCache($course->getId());
        // Update the users review history in session
        $userSession->saveUserInformationInSession();
        return $this->getAjaxResponse(true,$review->getId());
    }

    /**
     * Records the user feedback
     * @param $reviewId
     * @param $feedback
     */
    public function feedbackAction($reviewId, $feedback)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.context')->getToken()->getUser();
        if(!$user)
        {
            // No logged in user
            return $this->getAjaxResponse(false, "User is not logged in");
        }

        // Get the review
        $review = $em->getRepository('ClassCentralSiteBundle:Review')->find($reviewId);
        if(!$review)
        {
            return $this->getAjaxResponse(false, 'Review does not exist');
        }

        // Normalize the feedback
        $fb = ($feedback == 1) ? true: false;

        // Check if it already exists or not
        $rf = $em->getRepository('ClassCentralSiteBundle:ReviewFeedback')->findOneBy(array(
                'user' => $user,
                'review'=>$review
        ));

        if($rf)
        {
            $rf->setHelpful($fb);
        } else
        {

            // Create a new feedback
            $rf = new ReviewFeedback();
            $rf->setUser($user);
            $rf->setHelpful($fb);
            $rf->setReview($review);
        }
        $em->persist($rf);
        $em->flush();

        return $this->getAjaxResponse(true);
    }


    private function getAjaxResponse($success = false, $message = '')
    {
        $response = array('success' => $success, 'message' => $message);
        return new Response(json_encode($response));
    }

    /**
     * Admin access only - shows reviews by different status id
     * @param Request $request
     * @param $statusId
     */
   public function reviewsByStatusAction(Request $request, $statusId)
   {
       $em = $this->getDoctrine()->getManager();
       $reviews = $em->getRepository('ClassCentralSiteBundle:Review')->findByStatus($statusId);

       return $this->render('ClassCentralSiteBundle:Review:reviewsByStatus.html.twig',array(
            'reviews' => $reviews
       ));
   }

    /**
     * Shows the users reviews
     */
    public function myReviewsAction()
    {
        // Get the user
        $user = $this->get('security.context')->getToken()->getUser();
        $reviews = array();
        foreach($user->getReviews() as $review)
        {
            $reviews[] = ReviewUtility::getReviewArray($review);
        }

        return $this->render('ClassCentralSiteBundle:Review:myreviews.html.twig',array(
                'reviews' => $reviews,
                'page' => 'myReviews'
            ));
    }

}