<?php

namespace  ClassCentral\SiteBundle\Services;

use ClassCentral\SiteBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class UserSession
{
    private $securityContext;

    private $em;

    private $session;

    private $container;

    const MT_COURSE_KEY = 'mooc_tracker_courses';
    const MT_SEARCH_TERM_KEY = 'mooc_tracker_search_terms';
    const MT_REFERRAL_KEY = 'mooc_tracker_referral';
    const LIBRARY_COURSES_KEY = 'user_courses_library';
    const USER_RECENTLY_VIEWED = 'user_recently_view';
    const NEWSLETTER_USER_EMAIL = 'newsletter_user_email';
    const USER_REVIEWED_COURSES = 'user_review_course_ids';
    const USER_REVIEWS  = 'user_review_ids';

    // Flash message types
    const FLASH_TYPE_NOTICE = 'notice';
    const FLASH_TYPE_INFO = 'info';
    const FLASH_TYPE_SUCCESS = 'success';
    const FLASH_TYPE_ERROR = 'error';

    /**
     * Routes to skip when tracking the previous page.
     * This page is used to redirect login
     * @var array
     */
    private static $skipRoutes = array(
        'signup', 'signup_mooc', 'signup_search_term', 'signup_create_user',
        'forgotpassword', 'forgotpassword_sendemail', 'resetPassword', 'resetPassword_save',
        'fb_authorize_start', 'fb_authorize_redirect',
        'review_save', 'review_create','review_new',
        'login','github_btn'
    );

    private static $flashTypes = array(self::FLASH_TYPE_NOTICE, self::FLASH_TYPE_INFO, self::FLASH_TYPE_SUCCESS, self::FLASH_TYPE_ERROR);

    public function __construct(SecurityContext $securityContext, Doctrine $doctrine, Session $session, ContainerInterface $container)
    {
        $this->securityContext = $securityContext;
        $this->em              = $doctrine->getManager();
        $this->session         = $session;
        $this->container       = $container;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ($this->securityContext->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            $this->login($event->getAuthenticationToken()->getUser());
        }

        if ($this->securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED'))
        {
            // user has logged in using remember_me cookie
        }

        // do some other magic here
        // $user = $event->getAuthenticationToken()->getUser();

        // ...
    }

    /**
     * Saves the last route in the session
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() !== \Symfony\Component\HttpKernel\HttpKernel::MASTER_REQUEST) {
            return;
        }

        /** @var \Symfony\Component\HttpFoundation\Request $request  */
        $request = $event->getRequest();
        /** @var \Symfony\Component\HttpFoundation\Session $session  */
        $session = $request->getSession();

        $routeParams = $this->container->get('router')->match($request->getPathInfo());
        $routeName = $routeParams['_route'];
        if ($routeName[0] == '_') {
            return;
        }

        // Skype the route tracking for certain pages like signup, forgot password etc
        if(in_array($routeName,self::$skipRoutes))
        {
            return;
        }

        // If its starts with ajax_
        if(strpos($routeName, "ajax") === 0)
        {
            return;
        }
        unset($routeParams['_route']);
        $routeData = array('name' => $routeName, 'params' => $routeParams);

        //Skipping duplicates
        $thisRoute = $session->get('this_route', array());
        if ($thisRoute == $routeData) {
            return;
        }

        $logger = $this->container->get('logger');

        if(!empty($thisRoute))
        {
            $logger->info( " LOGIN PREVIOUS" . $thisRoute['name']);
        }
        $logger->info(" LOGIN CURRENT" . $routeData['name']);

        $session->set('last_route', $thisRoute);
        $session->set('this_route', $routeData);
    }

    public function login(User $user)
    {

        // Create a review for this user if it exists
        $us = $this->container->get('user_service');
        $us->addUserToReview($user);

        // user has just logged in. Update the session
        $this->saveUserInformationInSession();

        // Update the last login time stamp
        $user->setLastLogin(new \DateTime());
        $this->em->persist($user);
        $this->em->flush();

        // Send a successfull login notification
        $this->notifyUser(
            self::FLASH_TYPE_SUCCESS,
            'Logged in',
            'You have been logged in successfully'
        );
    }

    public function saveUserInformationInSession()
    {
        $user = $this->securityContext->getToken()->getUser();

        // Get MOOC tracker courseIds
        $courseIds = array();
        foreach($user->getMoocTrackerCourses() as $moocTrackerCourse)
        {
            $courseIds[] = $moocTrackerCourse->getCourse()->getId();
        }
        $this->session->set(self::MT_COURSE_KEY,$courseIds);

        // Search terms from MOOC tracker
        $searchTerms = array();
        foreach($user->getMoocTrackerSearchTerms() as $moocTrackerSearchTerm)
        {
            $searchTerms[] = $moocTrackerSearchTerm->getSearchTerm();
        }
        $this->session->set(self::MT_SEARCH_TERM_KEY, $searchTerms);

        // Save all the courses from users library in session
        $userCourseIds = array();
        foreach($user->getUserCourses() as $userCourse)
        {
            $courseId = $userCourse->getCourse()->getId();
            if(!isset($userCourseIds[$courseId]))
            {
                $userCourseIds[$courseId][] = array();
            }
            $userCourseIds[$courseId][] = $userCourse->getListId();
        }
        $this->session->set(self::LIBRARY_COURSES_KEY, $userCourseIds);

        $this->saveReviewInformationInSession();

    }

    /**
     * Saves the review history of the user in the session
     */
    public function saveReviewInformationInSession()
    {
        $user = $this->securityContext->getToken()->getUser();
        // Save all the course ids of the rhe reviews that the user has done in the session
        $reviewCourseIds = array();
        $reviewIds = array();
        foreach($user->getReviews() as $review )
        {
            $reviewCourseIds[] = $review->getCourse()->getId();
            $reviewIds[] = $review->getId();
        }
        $this->session->set(self::USER_REVIEWED_COURSES,$reviewCourseIds);
        $this->session->set(self::USER_REVIEWS,$reviewIds);
    }

    public function isCourseReviewed($courseId)
    {
        $courseIds = $this->session->get(self::USER_REVIEWED_COURSES);
        if(empty($courseIds))
        {
            return false;
        }
        return in_array($courseId, $courseIds);
    }

    public function isUserReview($reviewId)
    {
        $reviewIds = $this->session->get(self::USER_REVIEWS);
        if(empty($reviewIds))
        {
            return false;
        }
        return in_array($reviewId, $reviewIds);
    }

    /**
     * Checks whether the course has been added to MOOC tracker
     */
    public function isCourseAddedToMT($courseId)
    {
        $courseIds = $this->session->get(self::MT_COURSE_KEY);
        if(empty($courseIds))
        {
            return false;
        }
        return in_array($courseId, $courseIds);
    }

    /**
     * Returns a array of list ids with courses for a particular course
     * @param $courseId
     */
    public function getCourseListIds($courseId)
    {
        $userCourseIds = $this->session->get(self::LIBRARY_COURSES_KEY);
        return isset($userCourseIds[$courseId]) ? $userCourseIds[$courseId] : array();
    }

    /**
     * Checks whether the search term has been added to MOOC tracker
     * @param $searchTerm
     * @return bool
     */
    public function isSearchTermAddedToMT($searchTerm)
    {
        $searchTerms = $this->session->get(self::MT_SEARCH_TERM_KEY);
        if(empty($searchTerms))
        {
            return false;
        }
        return in_array($searchTerm,$searchTerms);
    }

    public function getMTCourses()
    {
        return $this->session->get(self::MT_COURSE_KEY);
    }

    public function getUserLibraryCourses()
    {
        return $this->session->get(self::LIBRARY_COURSES_KEY);
    }

    public function getMTSearchTerms()
    {
        return $this->session->get(self::MT_SEARCH_TERM_KEY);
    }

    public function saveSignupReferralDetails($details)
    {
         $this->session->set(self::MT_REFERRAL_KEY,$details);
    }

    public function getSignupReferralDetails()
    {
        return $this->session->get(self::MT_REFERRAL_KEY);
    }

    public function clearSignupReferralDetails()
    {
        return $this->session->remove(self::MT_REFERRAL_KEY);
    }

    /**
     * Saves recently viewed courses in session
     * @param $courseId
     */
    public function saveRecentlyViewed($courseId)
    {
        $courses = $this->getRecentlyViewed();
        if(empty($courses))
        {
            $courses = array();
            $courses[] = $courseId;
        }
        else
        {
            // Remove the course is it already exists
            $pos = array_search($courseId,$courses);
            if(is_numeric($pos))
            {
                unset($courses[$pos]);
            }

            // Push the course at the head
            array_unshift($courses, $courseId);

            // Save 5 courses
            $courses = array_slice($courses,0,5);
        }

        $this->session->set(self::USER_RECENTLY_VIEWED,$courses);
    }

    public function getRecentlyViewed()
    {
        return $this->session->get(self::USER_RECENTLY_VIEWED);
    }

    public function setNewsletterUserEmail($email)
    {
        $this->session->set(self::NEWSLETTER_USER_EMAIL, $email);
    }

    public function getNewsletterUserEmail()
    {
        return $this->session->get(self::NEWSLETTER_USER_EMAIL);
    }

    /**
     * Saves a flash message to be displayed to the user on the next page load
     * @param $type
     * @param $title
     * @param $text
     */
    public function notifyUser($type, $title, $text, $delay = 8)
    {
        if(!in_array($type, self::$flashTypes))
        {
            throw new \Exception('Find me a pair of courses');
        }

        $this->session->getFlashBag()->add($type, array(
                'title' => $title,
                'text' => $text,
                'delay' => $delay
            ));
    }
}
