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
use Symfony\Component\HttpFoundation\Request;

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
        $userId = $this->getJob()->getUserId();
        $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneBy( array( 'id' => $userId) );

        if(!$user)
        {
            return SchedulerJobAbstract::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "User with id $userId not found"
            );
        }

        $emailContent = $this->getFollowUpEmail( $user );

        return $this->sendEmail('Dhawal from Class Central',$emailContent, $user);

    }

    public function getFollowUpEmail(User $user)
    {
        $reviews = array();
        $data = $this->getContainer()->get('course_listing')->collection([2161,4319,461,2750,442],new Request(),[]);
        $courses = $data['courses']['hits']['hits'];

        $templating = $this->getContainer()->get('templating');
        $html = $templating->renderResponse(
            'ClassCentralMOOCTrackerBundle:User:newuser.followup.html.twig',array(
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
            'from' => '"Dhawal Shah" <d@class-central.com>',
            'to' => $user->getEmail(),
            'subject' => $subject,
            'html' => $html,
            'o:tag' => self::NEW_USER_FOLLOW_UP_CAMPAIGN_ID,
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

        return SchedulerJobStatus::getStatusObject(SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS, "Email sent");
    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }
}
