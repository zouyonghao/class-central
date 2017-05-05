<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/9/15
 * Time: 12:05 AM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;
use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserPreference;
use ClassCentral\SiteBundle\Services\Mailgun;
use ClassCentral\SiteBundle\Utility\CryptUtility;

/**
 * Sends a email asking users to write reviews for courses that have been
 * added to MOOC Tracker. If the courses have been rated then ask for
 * detailed reviews
 * Class ReviewSolicitationJob
 * @package ClassCentral\MOOCTrackerBundle\Job
 */
class ReviewSolicitationJob extends SchedulerJobAbstract {

    const REVIEW_SOLICITATION_JOB_TYPE = 'mt_ask_for_reviews_for_completed_courses';
    const REVIEW_SOLICITATION_CAMPAIGN_ID = 'mt_review_solicitation_for_completed_courses';

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
        $yesterday = new \DateTime( $args['date'] );
        $yesterday->sub( new \DateInterval('P1D'));

        if(!$user)
        {
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "User with id $userId not found"
            );
        }

        // Build a list of all users courses that were added yesterday
        $courses_added = array();
        foreach($user->getUserCourses() as $uc)
        {
            if( in_array( $uc->getListId(), array(3,4,5,6,7) )  )
            {
                $courses_added[ $uc->getCourse()->getId() ] = $uc->getCourse();
            }
        }

        // Figure out if there are ratings for any of those courses
        $courses_rated = array();
        foreach($user->getReviews() as $review)
        {
            if( isset($courses_added[ $review->getCourse()->getId() ]) )
            {
                // Check if the course has a review text
                $reviewText = $review->getReview();
                if( empty($reviewText) )
                {
                    // Course has no reviews. Ask for reviews
                    $courses_rated[ $review->getCourse()->getId() ]  = $review->getCourse();
                }

                unset ($courses_added[ $review->getCourse()->getId() ]);
            }
        }

        if(empty($courses_rated) && empty($courses_added) )
        {
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS,
                "No need to send review solicitation emails for user with user id $userId"
            );
        }
        else
        {
            // Send email
            if( count($courses_added) + count($courses_rated) == 1)
            {
               // Use template for single email
                $course = array_pop(array_merge($courses_added,$courses_rated));
                $emailContent = $this->getSingleCourseEmail( $course, $user );
                $subject = sprintf("Would you recommend  '%s' to others? Submit a review on Class Central",$course->getName());
                return $this->sendEmail($subject,$emailContent, $user);
            }
            else
            {
                $courses = array_merge($courses_added,$courses_rated);
                $emailContent = $this->getMultipleCoursesEmail( $courses, $user);
                $subject = 'Would you recommend any courses to others? Submit a review on Class Central';
                return $this->sendEmail($subject,$emailContent, $user);
            }
        }
    }

    /**
     * Generates the html content for single course email
     * @param $course
     * @param $user
     */
    private function getSingleCourseEmail(Course $course, User $user)
    {
        $templating = $this->getContainer()->get('templating');
        $html = $templating->renderResponse(
            'ClassCentralMOOCTrackerBundle:Review:single.course.inlined.html',array(
              'course' => $course,
              'user'   => $user,
              'loginToken' => $this->getContainer()->get('user_service')->getLoginToken($user,false),
              'baseUrl' => $this->getContainer()->getParameter('baseurl'),
              'jobType' => $this->getJob()->getJobType(),
               'unsubscribeToken' => CryptUtility::getUnsubscribeToken( $user,
                    UserPreference::USER_PREFERENCE_REVIEW_SOLICITATION,
                    $this->getContainer()->getParameter('secret')
                ),
                'utm' => array(
                    'medium'   => Mailgun::UTM_MEDIUM,
                    'campaign' => 'review_solicitation',
                    'source'   => Mailgun::UTM_SOURCE_PRODUCT,
                )
            )
        )->getContent();

        return $html;
    }

    /**
     * Generates the html content for multiple courses
     * @param $courses
     * @param User $user
     * @return mixed
     */
    private function getMultipleCoursesEmail($courses, User $user)
    {
        $templating = $this->getContainer()->get('templating');
        $html = $templating->renderResponse(
            'ClassCentralMOOCTrackerBundle:Review:multiple.courses.inlined.html',array(
                'courses' => $courses,
                'loginToken' => $this->getContainer()->get('user_service')->getLoginToken($user),
                'user'   => $user,
                'baseUrl' => $this->getContainer()->getParameter('baseurl'),
                'jobType' => $this->getJob()->getJobType(),
                'unsubscribeToken' => CryptUtility::getUnsubscribeToken( $user,
                        UserPreference::USER_PREFERENCE_REVIEW_SOLICITATION,
                        $this->getContainer()->getParameter('secret')
                    ),
                'utm' => array(
                    'medium'   => Mailgun::UTM_MEDIUM,
                    'campaign' => 'review_solicitation',
                    'source'   => Mailgun::UTM_SOURCE_PRODUCT,
                )
            )
        )->getContent();

        return $html;
    }

    /**
     * Sends the MOOC Tracker email
     * @param $subject
     * @param $html
     * @param User $user
     * @return SchedulerJobStatus
     */
    private function sendEmail( $subject, $html, User $user)
    {
        $mailgun = $this->getContainer()->get('mailgun');

        $response = $mailgun->sendMessage( array(
            'from' => '"Class Central" <no-reply@class-central.com>',
            'to' => $user->getEmail(),
            'subject' => $subject,
            'html' => $html,
            'o:tag' => self::REVIEW_SOLICITATION_CAMPAIGN_ID
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

        return SchedulerJobStatus::getStatusObject(SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS, "Email sent");
    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }
}