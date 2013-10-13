<?php

namespace  ClassCentral\SiteBundle\Services;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class UserSession
{
    private $securityContext;

    private $em;

    private $session;

    const MT_COURSE_KEY = 'mooc_tracker_courses';
    const MT_SEARCH_TERM_KEY = 'mooc_tracker_search_terms';


    public function __construct(SecurityContext $securityContext, Doctrine $doctrine, Session $session)
    {
        $this->securityContext = $securityContext;
        $this->em              = $doctrine->getManager();
        $this->session         = $session;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ($this->securityContext->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            // user has just logged in. Update the session
            $this->saveUserInformationInSession();

        }

        if ($this->securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED'))
        {
            // user has logged in using remember_me cookie
        }

        // do some other magic here
        // $user = $event->getAuthenticationToken()->getUser();

        // ...
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

}