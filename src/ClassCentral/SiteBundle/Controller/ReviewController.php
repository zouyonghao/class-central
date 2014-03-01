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
use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Form\SignupType;
use ClassCentral\SiteBundle\Services\UserSession;
use ClassCentral\SiteBundle\Utility\Breadcrumb;
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
            'isAdmin' =>  $this->get('security.context')->isGranted('ROLE_ADMIN')
        );
    }


    /**
     * Renders the form to create a new review for both logged in
     * and logged out users
     * @param Request $request
     * @param $courseId
     */
    public function newAction(Request $request, $courseId) {

        $loggedIn = $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY');
        $em = $this->getDoctrine()->getManager();

        // Get the course
        $course = $em->getRepository('ClassCentralSiteBundle:Course')->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        $formData = $this->getReviewFormData($course);
        $formData['page'] = 'write_review';
        $formData['review'] = new Review(); // Empty review object

        // Breadcrumbs
        $breadcrumbs = array();
        $initiative = $course->getInitiative();
        if(!empty($initiative))
        {
            $breadcrumbs[] = Breadcrumb::getBreadCrumb(
                $initiative->getName(),
                $this->generateUrl('ClassCentralSiteBundle_initiative',array('type' => $initiative->getCode() ))
            );
        }
        else
        {
            $breadcrumbs[] = Breadcrumb::getBreadCrumb(
                'Others',
                $this->generateUrl('ClassCentralSiteBundle_initiative',array('type' => 'others'))
            );
        }

        $breadcrumbs[] = Breadcrumb::getBreadCrumb(
            $course->getName(), $this->generateUrl('ClassCentralSiteBundle_mooc', array('id' => $course->getId(), 'slug' => $course->getSlug()))
        );

        $breadcrumbs[] = Breadcrumb::getBreadCrumb('Review');

        $formData['breadcrumbs'] = $breadcrumbs;
        if($loggedIn)
        {

            $user = $this->get('security.context')->getToken()->getUser();
            $review = $em->getRepository('ClassCentralSiteBundle:Review')->findOneBy(array(
                'user' => $user,
                'course' => $course
            ));

            if($review)
            {
                // redirect to edit page
                return $this->redirect($this->generateUrl('review_edit', array('reviewId' => $review->getId() )));
            }
            return $this->render('ClassCentralSiteBundle:Review:new.html.twig', $formData);
        }
        else
        {
            $signupForm   = $this->createForm(new SignupType(), new User(),array(
                'action' => $this->generateUrl('signup_create_user')
            ));
            $formData['signupForm'] = $signupForm->createView();
            return $this->render('ClassCentralSiteBundle:Review:newUserReview.html.twig', $formData);
        }

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
        return $this->render('ClassCentralSiteBundle:Review:new.html.twig', $formData);
    }



    /**
     * Validates and creates the review. Updates if the review already
     * is created
     * @param Request $request
     * @param $courseId
     */
    public function createAction(Request $request, $courseId) {
        $logger = $this->get('logger');
        $user = $this->container->get('security.context')->getToken()->getUser();
        $ru = $this->get('review');
        // Get the json post data
        $content = $this->getRequest("request")->getContent();
        if(empty($content)) {
            return $this->getAjaxResponse(false, "Error retrieving form details");
        }
        $reviewData = json_decode($content, true);

        $isAdmin = $this->get('security.context')->isGranted('ROLE_ADMIN');
        $result = $ru->saveReview($courseId, $user, $reviewData, $isAdmin);

        if(is_string($result))
        {
            // Error. Json response. I know this is wrong
            return new Response($result);
        }

        // result is a review object
        $review = $result;

        return $this->getAjaxResponse(true,$review->getId());
    }


    /**
     * Part of the review signup flow. Saves the review in session, until
     * user signs up and login
     * @param Request $request
     * @param $courseId
     */
    public function saveAction(Request $request, $courseId)
    {
        $em = $this->getDoctrine()->getManager();
        $logger = $this->get('logger');
        $ru = $this->get('review');
        $userSession = $this->get('user_session');
        $session = $this->get('session');

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

        // Progress is required
        if(!isset($reviewData['progress']) && !array_key_exists($reviewData['progress'], UserCourse::$progress))
        {
            return $this->getAjaxResponse(false,'Progress is required');
        }

        // Save it in the session
        $reviewData['courseId'] = $courseId;
        $session->set('user_review',$reviewData);

        return $this->getAjaxResponse(true);
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