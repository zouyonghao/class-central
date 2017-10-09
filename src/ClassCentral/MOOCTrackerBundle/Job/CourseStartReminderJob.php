<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/24/14
 * Time: 8:32 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;
use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Entity\UserPreference;
use ClassCentral\SiteBundle\Services\Mailgun;
use ClassCentral\SiteBundle\Utility\CourseUtility;
use ClassCentral\SiteBundle\Utility\CryptUtility;
use ClassCentral\SiteBundle\Utility\ReviewUtility;
use InlineStyle\InlineStyle;

/**
 * Sends a reminder email for the course. Emails are sent on 2 occasion
 * - 2 weeks before the start date
 * - 1 day before
 * Class CourseStartReminderJob
 * @package ClassCentral\MOOCTrackerBundle\Job
 */
class CourseStartReminderJob extends SchedulerJobAbstract{

    const JOB_TYPE_2_WEEKS_BEFORE = 'email_reminder_course_start_2weeks';
    const JOB_TYPE_1_DAY_BEFORE   = 'email_reminder_course_start_1day';

    // Mailgun campaign ids
    const MAILGUN_2_WEEKS_BEFORE_CAMPAIGN_ID = 'mt_mooc_start_two_weeks';
    const MAILGUN_1_DAY_BEFORE_CAMPAIGN_ID = 'mt_mooc_start_1_day';

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
        $rs = $this->getContainer()->get('review');

        if(!$user)
        {
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "User with id $userId not found"
            );
        }

        $campaignId = ( $this->getJob()->getJobType() == self::JOB_TYPE_1_DAY_BEFORE )  ?
                           self::MAILGUN_1_DAY_BEFORE_CAMPAIGN_ID : self::MAILGUN_2_WEEKS_BEFORE_CAMPAIGN_ID;

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


        $numCourses = 0;
        if(isset( $args[UserCourse::LIST_TYPE_INTERESTED]) )
        {
            $numCourses += count($args[UserCourse::LIST_TYPE_INTERESTED]);
        }
        if( isset($args[UserCourse::LIST_TYPE_ENROLLED]) )
        {
            $numCourses += count( $args[UserCourse::LIST_TYPE_ENROLLED] );
        }


        if( $numCourses == 0 )
        {
            // Don't send an email
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "$campaignId : No courses found for user $userId"
            );
        }
        elseif( $numCourses == 1)
        {
           // Single course
            $courseId = null;

            $isInterested = empty( $args[UserCourse::LIST_TYPE_INTERESTED] ) ? false : true;
            if( $isInterested )
            {
                $courseId = array_pop( $args[UserCourse::LIST_TYPE_INTERESTED]) ;
            }
            else
            {
                $courseId = array_pop( $args[UserCourse::LIST_TYPE_ENROLLED] );
            }
            $course = $em->getRepository('ClassCentralSiteBundle:Course')->find( $courseId );
            $html = $this->getSingleCourseEmail( $course, $isInterested, $user, $this->getJob()->getJobType(), $counts );

            $subject = "Reminder : ";
            $subject .= $course->getName() ;
            $subject .= ( $this->getJob()->getJobType() == self::JOB_TYPE_1_DAY_BEFORE )  ?
                " starts tomorrow" : " starts soon";

            return $this->sendEmail( $subject, $html, $user,$campaignId);

        }
        else
        {
            // Multiple courses. Build a array
            $courses = array();
            if(isset( $args[UserCourse::LIST_TYPE_INTERESTED]) )
            {
                foreach( $args[UserCourse::LIST_TYPE_INTERESTED] as $courseId)
                {
                    $course =  $em->getRepository('ClassCentralSiteBundle:Course')->find( $courseId );

                    // Get the review details
                    $courseArray = $em->getRepository('ClassCentralSiteBundle:Course')->getCourseArray( $course );
                    $courseArray['rating'] = $rs->getRatings($course->getId());
                    $courseArray['ratingStars'] = ReviewUtility::getRatingStars( $courseArray['rating'] );
                    $rArray = $rs->getReviewsArray($course->getId());
                    $courseArray['reviewsCount'] = $rArray['count'];

                    $courses[] = array(
                        'interested' => true,
                        'id' => $courseId,
                        'course' => $courseArray
                    );
                }
            }
            if( isset($args[UserCourse::LIST_TYPE_ENROLLED]) )
            {
                foreach( $args[UserCourse::LIST_TYPE_ENROLLED] as $courseId)
                {
                    $course =  $em->getRepository('ClassCentralSiteBundle:Course')->find( $courseId );

                    // Get the review details
                    $courseArray = $em->getRepository('ClassCentralSiteBundle:Course')->getCourseArray( $course );
                    $courseArray['rating'] = $rs->getRatings($course->getId());
                    $courseArray['ratingStars'] = ReviewUtility::getRatingStars( $courseArray['rating'] );
                    $rArray = $rs->getReviewsArray($course->getId());
                    $courseArray['reviewsCount'] = $rArray['count'];

                    $courses[] = array(
                        'interested' => false,
                        'id' => $courseId,
                        'course' => $courseArray
                    );
                }
            }

            $html = $this->getMultipleCouresEmail( $courses, $user, $counts );
            $subject = "Reminder : $numCourses courses are";
            $subject .= ( $this->getJob()->getJobType() == self::JOB_TYPE_1_DAY_BEFORE )  ?
                " starting tomorrow" : " starting soon";

            return $this->sendEmail(
                $subject, $html, $user,$campaignId
            );

        }

    }

    private function getSingleCourseEmail($course, $isInterested, $user, $jobType, $counts)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();
        $rs = $this->getContainer()->get('review');
        $courseArray = $em->getRepository('ClassCentralSiteBundle:Course')->getCourseArray( $course );
        $courseArray['rating'] = $rs->getRatings($course->getId());
        $courseArray['ratingStars'] = ReviewUtility::getRatingStars( $courseArray['rating'] );
        $rArray = $rs->getReviewsArray($course->getId());
        $courseArray['reviewsCount'] = $rArray['count'];

        $templating = $this->getContainer()->get('templating');
        return $templating->renderResponse('ClassCentralMOOCTrackerBundle:Reminder:single.course.inlined.html', array(
            'course' => $courseArray,
            'baseUrl' => $this->getContainer()->getParameter('baseurl'),
            'loginToken' => $this->getContainer()->get('user_service')->getLoginToken($user, false),
            'interested' => $isInterested,
            'user' => $user,
            'jobType' => $jobType,
            'counts' => $counts,
            'unsubscribeToken' => CryptUtility::getUnsubscribeToken( $user,
                    UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES,
                    $this->getContainer()->getParameter('secret')
                ),
            'utm' => array(
                'medium'   => Mailgun::UTM_MEDIUM,
                'campaign' => 'mt_course_start_reminder',
                'source'   => Mailgun::UTM_SOURCE_PRODUCT,
            )
        ))->getContent();

    }

    private function getMultipleCouresEmail( $courses, User $user, $counts )
    {
        $templating = $this->getContainer()->get('templating');
        $html = $templating->renderResponse('ClassCentralMOOCTrackerBundle:Reminder:multiple.courses.inlined.html', array(
            'courses' => $courses,
            'baseUrl' => $this->getContainer()->getParameter('baseurl'),
            'user' => $user,
            'jobType' => $this->getJob()->getJobType(),
            'loginToken' => $this->getContainer()->get('user_service')->getLoginToken($user,false),
            'counts' => $counts,
            'unsubscribeToken' => CryptUtility::getUnsubscribeToken( $user,
                    UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES,
                    $this->getContainer()->getParameter('secret')
                ),
            'utm' => array(
                'medium'   => Mailgun::UTM_MEDIUM,
                'campaign' => 'mt_course_start_reminder',
                'source'   => Mailgun::UTM_SOURCE_PRODUCT,
            )
        ))->getContent();

        return $html;
    }

    /**
     * Sends the MOOC Tracker email
     * @param $subject
     * @param $html
     * @param User $user
     * @return SchedulerJobStatus
     */
    private function sendEmail( $subject, $html, User $user, $campaignId)
    {
        $mailgun = $this->getContainer()->get('mailgun');

        $email = $user->getEmail();
        $env = $this->getContainer()->getParameter('kernel.environment');
        if($env !== 'prod')
        {
            $email = $this->getContainer()->getParameter('test_email');
        }

        $response = $mailgun->sendMessage( array(
            'from' => '"Class Central\'s MOOC Tracker" <no-reply@class-central.com>',
            'to' => $email,
            'subject' => $subject,
            'html' => $html,
            'o:tag' => $campaignId,
            'v:my-custom-data' => \ClassCentral\SiteBundle\Services\User::getUserMetaDataForAnalyticsJson($user)
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

        return SchedulerJobStatus::getStatusObject(SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS, "$campaignId: Email sent for ". $user->getId());
    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }
}