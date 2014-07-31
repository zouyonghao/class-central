<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 7/30/14
 * Time: 5:42 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;
use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus;
use ClassCentral\SiteBundle\Controller\NavigationController;
use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Entity\UserPreference;
use ClassCentral\SiteBundle\Utility\CryptUtility;

class CourseNewSessionJob extends SchedulerJobAbstract{

    const JOB_TYPE_NEW_SESSION = 'email_notification_new_session_2weeks';
    const MAILGUN_MT_NEW_SESSION = 'mt_mooc_new_session';

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
        // Get counts for self paced and recently started courses
        $navController = new NavigationController();
        $counts = $navController->getNavigationCounts( $this->getContainer() );

        /**
         * Send notifications only for user courses
         */
        if(isset( $args[UserCourse::LIST_TYPE_INTERESTED]) )
        {
            $courseIds = $args[UserCourse::LIST_TYPE_INTERESTED];
            $numCourses = count( $courseIds );
            if( $numCourses == 1)
            {
                $courseId = $courseIds[0];
                $course =  $em->getRepository('ClassCentralSiteBundle:Course')->find( $courseId );
                $subject = $course->getName() . ' - New session added';

            }
            else
            {
                // Multiple courses
                $subject = "Course Notification: New sessions added for {$numCourses} courses";
            }

            // Build a list of courses for which notifications are being sent for
            $courses = array();
            foreach( $courseIds as $cid)
            {
                $course =  $em->getRepository('ClassCentralSiteBundle:Course')->find( $cid );
                $courses[] = array(
                    'interested' => true,
                    'id' => $cid,
                    'course' => $em->getRepository('ClassCentralSiteBundle:Course')->getCourseArray( $course )
                );
            }

            $templating = $this->getContainer()->get('templating');
            $html = $templating->renderResponse(
                'ClassCentralMOOCTrackerBundle:NewSession:newSession.inlined.html', array(
                'results' => $courses,
                'baseUrl' => $this->getContainer()->getParameter('baseurl'),
                'user' => $user,
                'numCourses' => $numCourses,
                'showDesc' => ($numCourses <= 10),
                'coursesHidden' => ($numCourses > 40),
                'counts' => $counts,
                'unsubscribeToken' => CryptUtility::getUnsubscribeToken( $user,
                        UserPreference::USER_PREFERENCE_MOOC_TRACKER_COURSES,
                        $this->getContainer()->getParameter('secret')
                    )
            ))->getContent();

            $campaignId = self::MAILGUN_MT_NEW_SESSION;

            return $this->sendEmail(
                $subject,
                $html,
                $user,
                $campaignId,
                $numCourses
            );

        }
        else
        {
            // Don't send an email
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "No courses found for user $userId"
            );
        }

    }

    private function sendEmail( $subject, $html, User $user, $campaignId, $count)
    {
        $mailgun = $this->getContainer()->get('mailgun');

        $response = $mailgun->sendMessage( array(
            'from' => '"MOOC Tracker" <no-reply@class-central.com>',
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
            "Course New Session notification sent for {$count} courses to user with id {$user->getId()}"
        );
    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }


}