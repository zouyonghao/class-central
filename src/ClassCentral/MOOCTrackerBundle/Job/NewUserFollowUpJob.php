<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/12/15
 * Time: 7:15 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;
use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus;
use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserPreference;
use ClassCentral\SiteBundle\Services\Mailgun;
use ClassCentral\SiteBundle\Utility\CryptUtility;
use ClassCentral\SiteBundle\Utility\ReviewUtility;

class NewUserFollowUpJob extends SchedulerJobAbstract {

    const NEW_USER_FOLLOW_UP_JOB_TYPE = 'mt_new_user_follow_up';
    const NEW_USER_FOLLOW_UP_CAMPAIGN_ID = 'mt_new_user_follow_up';

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
        $reviewService = $this->getContainer()->get('review');
        $userId = $this->getJob()->getUserId();
        $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneBy( array( 'id' => $userId) );

        if(!$user)
        {
            return SchedulerJobAbstract::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "User with id $userId not found"
            );
        }
        $courses = array();
        $reviews = array();
        foreach( array(2161,4319,461,2750,442) as $courseId )
        {
            $courses[] = $em->getRepository('ClassCentralSiteBundle:Course')->find( $courseId);
            $review = $reviewService->getReviewsArray($courseId );
            $rating = $reviewService->getBayesianAverageRating($courseId);
            $reviews[ $courseId ] = array_merge( $review, array(
                'starRating'=> ReviewUtility::getRatingStars($rating)
            ));
        }

        $emailContent = $this->getFollowUpEmail( $user, $courses, $reviews );

        return $this->sendEmail('Dhawal from Class Central',$emailContent, $user);

    }

    private function getFollowUpEmail(User $user, $courses, $reviews)
    {
        $templating = $this->getContainer()->get('templating');
        $html = $templating->renderResponse(
            'ClassCentralMOOCTrackerBundle:User:newuser.followup.inlined.html',array(
                'user'   => $user,
                'loginToken' => $this->getContainer()->get('user_service')->getLoginToken($user,false),
                'baseUrl' => $this->getContainer()->getParameter('baseurl'),
                'jobType' => $this->getJob()->getJobType(),
                'courses' =>$courses,
                'reviews' => $reviews,
                'utm' => array(
                    'medium'   => Mailgun::UTM_MEDIUM,
                    'campaign' => 'new_user_followup',
                    'source'   => Mailgun::UTM_SOURCE_PRODUCT,
                ),
                'unsubscribeToken' => CryptUtility::getUnsubscribeToken( $user,
                        UserPreference::USER_PREFERENCE_FOLLOW_UP_EMAILs,
                        $this->getContainer()->getParameter('secret')
                    )
            )
        )->getContent();

        return $html;
    }

    private function sendEmail( $subject, $html, User $user)
    {
        $mailgun = $this->getContainer()->get('mailgun');

        $response = $mailgun->sendMessage( array(
            'from' => '"Dhawal Shah" <dhawal@class-central.com>',
            'to' => $user->getEmail(),
            'subject' => $subject,
            'html' => $html,
            'o:campaign' => self::NEW_USER_FOLLOW_UP_CAMPAIGN_ID
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