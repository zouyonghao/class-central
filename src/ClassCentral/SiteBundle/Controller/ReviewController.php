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
use Symfony\Component\HttpKernel\Exception\HttpException;

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

        // Autologin if a token exists
        $this->get('user_service')->autoLogin($request);

        $em = $this->getDoctrine()->getManager();
        $loggedIn = $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY');
        $isAdmin = $this->get('security.context')->isGranted('ROLE_ADMIN');

        // Check if log in is required
        $login = $request->query->get('login');
        if($login && $login == 1 && !$loggedIn)
        {
            // Redirect to the login screen
            return $this->redirect( $this->generateUrl('login') );
        }

        // Get the course
        $course = $em->getRepository('ClassCentralSiteBundle:Course')->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        // IF the review is already created redirect to an edit review
        if($loggedIn)
        {
            $user = $this->get('security.context')->getToken()->getUser();
            $review = $em->getRepository('ClassCentralSiteBundle:Review')->findOneBy(array(
                'user' => $user,
                'course' => $course
            ));

            if(!$isAdmin && $review)
            {
                // redirect to edit page
                return $this->redirect($this->generateUrl('review_edit', array('reviewId' => $review->getId() )));
            }
        }

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

        return $this->render('ClassCentralSiteBundle:Review:review.html.twig', array(
            'page' => 'write_review',
            'course' => $course,
            'breadcrumbs' => $breadcrumbs,
            'reviewId' => null
        ));
    }

    /**
     * Route to generate review forms
     * @param Request $request
     * @param $courseId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function reviewFormAction(Request $request, $courseId, $page, $reviewId = null)
    {
        $loggedIn = $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY');
        $em = $this->getDoctrine()->getManager();
        $isAdmin = $this->get('security.context')->isGranted('ROLE_ADMIN');
        $user = $this->get('security.context')->getToken()->getUser();

        $course = null;
        $review = null;
        if( $reviewId != null )
        {
            $review = $em->getRepository('ClassCentralSiteBundle:Review')->find($reviewId);
            if(!$review)
            {
                // Show an error page
                throw $this->createNotFoundException('Unable to find Review entity.');
            }

            // Either the user is an admin or the person who created the review
            if(!$isAdmin && $user->getId() != $review->getUser()->getId())
            {
                exit("dfadsfasfsf");
                return "You do not have access to this page";
            }
            $course = $review->getCourse();
        }
        else
        {
            // Get the course
            $course = $em->getRepository('ClassCentralSiteBundle:Course')->find($courseId);
            if (!$course) {
                throw $this->createNotFoundException('Unable to find Course entity.');
            }
            $review = new Review();
        }

        $formData = $this->getReviewFormData($course);
        $formData['page'] = $page;
        $formData['review'] = $review;

        if($loggedIn)
        {

            $user = $this->get('security.context')->getToken()->getUser();
            $review = $em->getRepository('ClassCentralSiteBundle:Review')->findOneBy(array(
                'user' => $user,
                'course' => $course
            ));

            return $this->render('ClassCentralSiteBundle:Review:helpers\fullReviewForm.html.twig', $formData);
        }
        else
        {
            $signupForm   = $this->createForm(new SignupType(), new User(),array(
                'action' => $this->generateUrl('signup_create_user')
            ));
            $formData['signupForm'] = $signupForm->createView();
            return $this->render('ClassCentralSiteBundle:Review:helpers\partialReviewForm.html.twig', $formData);
        }
    }


    /**
     * Renders the edit form
     * @param Request $request
     * @param $reviewId
     */
    public function editAction(Request $request, $reviewId)
    {
        $em = $this->getDoctrine()->getManager();

        $review = $em->getRepository('ClassCentralSiteBundle:Review')->find($reviewId);
        if(!$review)
        {
            // Show an error page
            return null;
        }

        $course = $review->getCourse();

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

        return $this->render('ClassCentralSiteBundle:Review:review.html.twig', array(
            'page' => 'edit_course',
            'course' => $course,
            'reviewId' => $reviewId,
            'breadcrumbs' => $breadcrumbs
        ));
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

        // Save the review

        $user = $em->getRepository('ClassCentralSiteBundle:User')->getReviewUser();
        $result = $ru->saveReview($courseId, $user, $reviewData, true);

        if(is_string($result))
        {
            // Error. Json response. I know this is wrong
            return new Response($result);
        }

        // result is a review object
        $review = $result;

        //$session->set('user_review',$reviewData);
        // save the review id in the session.
        $session->set('user_review_id', $review->getId());
        $session->set('user_course_reviewed_for', $review->getCourse()->getId() ); // don't allow the user to write a review

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

        // Get the review
        $review = $em->getRepository('ClassCentralSiteBundle:Review')->find($reviewId);
        if(!$review)
        {
            return $this->getAjaxResponse(false, 'Review does not exist');
        }

        // Normalize the feedback
        $fb = ($feedback == 1) ? true: false;
        $rf = null;
        if(!$this->container->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            // Not logged in
            // Get the session
            $session = $this->getRequest()->getSession();
            if(!$session->isStarted())
            {
                // Start the session if its not already started
                $session->start();
            }
            $sessionId = $session->getId();


            $rf = $em->getRepository('ClassCentralSiteBundle:ReviewFeedback')->findOneBy(array(
                'sessionId' => $sessionId,
                'review'=>$review
            ));
            if ($rf)
            {
                $rf->setHelpful($fb);
            }
            else
            {
                // Create a new feedback
                $rf = new ReviewFeedback();
                $rf->setSessionId( $sessionId );
                $rf->setHelpful($fb);
                $rf->setReview($review);
            }

        }
        else
        {
            // Logged in user
            $user = $this->get('security.context')->getToken()->getUser();

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

    /**
     * Renders the review widget
     * @param Request $request
     */
    public function getReviewWidgetAction(Request $request)
    {

        $cache = $this->get('Cache');
        $courseId = $request->query->get('course-id');
        $courseCode = $request->query->get('course-code');

        // Basic check
        if( (empty($courseId) || !is_numeric($courseId)) && empty($courseCode) )
        {
            // This returns an empty blank page
            return $this->render('ClassCentralSiteBundle:Review:review.widget.html.twig', array(
                'course' => null
            ));
        }

        $data = $cache->get( $this->generateReviewWidgetCacheKey( $courseId, $courseCode ),function() use ($courseId,$courseCode){

            $em = $this->getDoctrine()->getManager();
            $rs = $this->get('review');

            // STEP 1: Figure out which course it is
            $course = null;


            if( !empty($courseId) and is_numeric( $courseId ) )
            {
                $course = $em->getRepository('ClassCentralSiteBundle:Course')->find( $courseId );
            }

            if( empty($course) && !empty( $courseCode ) )
            {
                $course = $em->getRepository('ClassCentralSiteBundle:Course')
                    ->findOneBy( array(
                        'shortName' => $courseCode
                    ) );
                $courseId = $course->getId();
            }

            if( $course )
            {
                // Step 2: Get 5 reviews that are to be displayed
                $query = $em->createQueryBuilder();
                $query->add('select', 'r')
                    ->add('from', 'ClassCentralSiteBundle:Review r')
                    ->join('r.reviewSummary','rs')
                    ->add('orderBy', 'r.rating DESC')
                    ->add('where', 'r.course = :course')
                    ->andWhere('rs is NOT NULL')
                    ->andWhere('r.status = :status')
                    ->setMaxResults(5)
                    ->setParameter('course', $course)
                    ->setParameter(':status', Review::REVIEW_STATUS_APPROVED);

                $reviewsWithSummaries = array();
                foreach ( $query->getQuery()->getResult() as $review )
                {
                    $reviewsWithSummaries[] = ReviewUtility::getReviewArray( $review );
                }

                // Get reviews and ratings count
                $rating = $rs->getRatings($courseId);
                $reviews = $rs->getReviews($courseId);

                return array(
                    'reviews' => $reviews,
                    'rating'  => $rating,
                    'formattedRating' => ReviewUtility::formatRating( $rating ),
                    'reviewsWithSummaries' => $reviewsWithSummaries,
                    'course' => $em->getRepository('ClassCentralSiteBundle:Course')->getCourseArray( $course )
                );
            }
            else {

                return array(
                    'course' => null
                );
            }



        });

        return $this->render('ClassCentralSiteBundle:Review:review.widget.html.twig', $data);
    }

    private function generateReviewWidgetCacheKey($courseId, $courseCode)
    {
        if(!empty($courseId))
        {
            return 'review_widget_course_id' . $courseId;
        }

        return 'review_widget_course_code' . strtolower($courseCode);
    }

}