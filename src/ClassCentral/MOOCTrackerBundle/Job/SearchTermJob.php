<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/2/14
 * Time: 7:18 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;
use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserPreference;
use ClassCentral\SiteBundle\Services\Mailgun;
use ClassCentral\SiteBundle\Utility\CryptUtility;

class SearchTermJob extends SchedulerJobAbstract {

    const JOB_TYPE_NEW_COURSES = 'mt_search_new_courses'; // Send notification for newly added courses
    const JOB_TYPE_RECENT_COURSES = 'mt_search_recent_courses'; // Send notifications for courses that are about to start

    // Mailgun Campaign ids
    const MAILGUN_MT_SEARCH_NEW_COURSES = 'mt_search_new_courses';
    const MAILGUN_MT_SEARCH_RECENT_COURSES = 'mt_search_recent_courses';
    public function setUp()
    {
        // TODO: Implement setUp() method.
    }

    /**
     * Must return an object of type SchedulerJobStatus
     * @param $args
     * @return SchedulerJobStatus
     */
    public function perform($args)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $userId = $this->getJob()->getUserId();
        $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneBy( array( 'id' => $userId) );
        $cache = $this->getContainer()->get('cache');
        $jobType = $this->getJob()->getJobType();
        $esCourses =  $this->getContainer()->get('es_courses');

        if(!$user)
        {
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "User with id $userId not found"
            );
        }

        // Sets a campaign id
        $campaignId = '';

        // Get all the search terms for the user
        $searchTerms = $this->getSearchTerms( $user );

        $session = array();
        $session[] = 'upcoming';

        if ( $jobType == self::JOB_TYPE_NEW_COURSES )
        {
            // upcoming, recentlyAdded
            $session[] = 'recentlyadded'; // Lowercase because of how elastic search tokenizes it
            $campaignId = self::MAILGUN_MT_SEARCH_NEW_COURSES;
        }
        elseif ( $jobType == self::JOB_TYPE_RECENT_COURSES )
        {
            // recent, upcoming
            $session[] = 'recent';
            $campaignId = self::MAILGUN_MT_SEARCH_RECENT_COURSES;
        }


        $courses = array();
        $count = 0;

        foreach( $searchTerms as $q )
        {

           $results = $esCourses->search( $q, $session );
           $total = $results['results']['hits']['total'];
           if( $total > 0)
           {
               $c = array();
               foreach($results['results']['hits']['hits'] as $result)
               {
                   $c[] = $result['_source'];
               }

               $courses[] = array(
                   'query' => $q,
                   'courses' => $c,
                   'count' => $total
               );

               $count += $total;
           }
        }

        if(  $count == 0 )
        {
            // No need to send an email
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS,
                "No courses for User with id $userId were found for job $jobType"
            );
        }
        $coursesText = 'courses';
        if( $count == 1)
        {
            $coursesText = 'course';
        }
        // Courses found. Get the template and send the email
        if ( $jobType == self::JOB_TYPE_NEW_COURSES )
        {
            $subject = "Search Notification: {$count} new {$coursesText} found";
        }
        elseif ( $jobType == self::JOB_TYPE_RECENT_COURSES )
        {
            $subject = "Search Notification: {$count} {$coursesText} starting soon";
        }

        $templating = $this->getContainer()->get('templating');

        $html = $templating->renderResponse(
            'ClassCentralMOOCTrackerBundle:Search:search.inlined.html', array(
            'results' => $courses,
            'baseUrl' => $this->getContainer()->getParameter('baseurl'),
            'user' => $user,
            'jobType' => $jobType,
            'numCourses' => $count,
            'loginToken' => $this->getContainer()->get('user_service')->getLoginToken($user,false),
            'showDesc' => ($count <= 10),
            'coursesHidden' => ($count > 40),
            'counts' => $this->getCounts(),
            'unsubscribeToken' => CryptUtility::getUnsubscribeToken( $user,
                    UserPreference::USER_PREFERENCE_MOOC_TRACKER_SEARCH_TERM,
                    $this->getContainer()->getParameter('secret')
                ),
            'utm' => array(
                'medium'   => Mailgun::UTM_MEDIUM,
                'campaign' => 'mt_search_notification',
                'source'   => Mailgun::UTM_SOURCE_PRODUCT,
            )
        ))->getContent();

        return $this->sendEmail(
            $subject,
            $html,
            $user,
            $campaignId,
            $count
        );
    }

    private function sendEmail( $subject, $html, User $user, $campaignId, $count)
    {
        $mailgun = $this->getContainer()->get('mailgun');

        $response = $mailgun->sendMessage( array(
            'from' => '"Class Central\'s MOOC Tracker" <no-reply@class-central.com>',
            'to' => $user->getEmail(),
            'subject' => $subject,
            'html' => $html,
            'o:campaign' => $campaignId
        ));

        if( !($response && $response->http_response_code == 200))
        {
            // Failed
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                ($response && $response->http_response_body)  ?
                    $response->http_response_body->message : "Mailgun error"
            );
        }

        return SchedulerJobStatus::getStatusObject(SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS,
            "Search notification sent for {$count} courses to user with id {$user->getId()}"
        );
    }

    /***
     * Returns the search terms for the user
     */
    private function getSearchTerms( User $user )
    {
        $st = array();
        foreach($user->getMoocTrackerSearchTerms() as $s)
        {
            $st[] = $s->getSearchTerm();
        }

        return $st;
    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }

    private function getCounts()
    {
        $cache = $this->getContainer()->get('cache');
        // Get counts for self paced and recently started courses
        $counts = $cache->get( 'mt_courses_count', function( $container){
            $esCourses = $container->get('es_courses');
            $counts = $esCourses->getCounts();
            $em = $container->get('doctrine')->getManager();

            $offeringCount = array();
            foreach (array_keys(Offering::$types) as $type)
            {
                if(isset($counts['sessions'][strtolower($type)]))
                {
                    $offeringCount[$type] = $counts['sessions'][strtolower($type)];
                }
                else
                {
                    $offeringCount[$type] = 0;
                }
            }

            return compact('offeringCount');

        }, array( $this->getContainer() ) );

        return $counts;
    }
}