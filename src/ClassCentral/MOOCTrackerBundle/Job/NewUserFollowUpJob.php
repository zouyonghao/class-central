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

        $courses = $em->getRepository('ClassCentralSiteBundle:Course')->findById(array(2161,361,1527,889,442)); ;
        $emailContent = $this->getFollowUpEmail( $user, $courses );

        return $this->sendEmail('Dhawal from Class Central',$emailContent, $user);

    }

    private function getFollowUpEmail(User $user, $courses)
    {
        $templating = $this->getContainer()->get('templating');
        $html = $templating->renderResponse(
            'ClassCentralMOOCTrackerBundle:User:newuser.followup.inlined.html',array(
                'user'   => $user,
                'loginToken' => $this->getContainer()->get('user_service')->getLoginToken($user),
                'baseUrl' => $this->getContainer()->getParameter('baseurl'),
                'jobType' => $this->getJob()->getJobType(),
                'courses' =>$courses,
                'unsubscribeToken' => CryptUtility::getUnsubscribeToken( $user,
                        UserPreference::USER_PREFERENCE_FOLLOW_UP_EMAILs,
                        $this->getContainer()->getParameter('secret')
                    )
            )
        )->getContent();
    }

    private function sendEmail( $subject, $html, User $user)
    {
        $mailgun = $this->getContainer()->get('mailgun');

        $response = $mailgun->sendMessage( array(
            'from' => '"Class Central" <dhawal@class-central.com>',
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