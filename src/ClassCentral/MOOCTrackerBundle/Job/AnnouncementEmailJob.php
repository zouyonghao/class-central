<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 12/25/15
 * Time: 9:31 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;
use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus;
use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserPreference;
use ClassCentral\SiteBundle\Services\Mailgun;
use ClassCentral\SiteBundle\Utility\CryptUtility;

class AnnouncementEmailJob extends SchedulerJobAbstract {

    const ANNOUNCEMENT_EMAIL_JOB_TYPE = 'mt_announcement_email_job_type';

    public function setUp()
    {
        // TODO: Implement setUp() method.
    }

    public function perform($args)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $userId = $this->getJob()->getUserId();
        $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneBy( array( 'id' => $userId) );

        if(!$user)
        {
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "User with id $userId not found"
            );
        }

        // Get the details
        $template = $args['template'];
        $subject = $args['subject'];
        $campaginId = $args['campaignId'];
        $deliveryTime = $args['deliveryTime'];

        // Check if the template file exists
        $templateFile = "src/ClassCentral/MOOCTrackerBundle/Resources/views/Announcement/" . $template;
        if(!file_exists($templateFile))
        {
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "Template $template does not exist"
            );
        }


        $emailContent = $this->getAnnouncementHTML($user,$template,$campaginId);

        return $this->sendEmail(
            $subject,
            $emailContent,
            $user,
            $campaginId,
            $deliveryTime
        );

    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }

    /**
     * Build out the html
     * @param User $user
     * @param $template
     * @param $campaignId
     * @return mixed
     */
    private function getAnnouncementHTML(User $user, $template,$campaignId)
    {
        $templating = $this->getContainer()->get('templating');
        $html = $templating->renderResponse(
            'ClassCentralMOOCTrackerBundle:Announcement:'.$template,array(
                'user'   => $user,
                'loginToken' => $this->getContainer()->get('user_service')->getLoginToken($user,false),
                'baseUrl' => $this->getContainer()->getParameter('baseurl'),
                'jobType' => $this->getJob()->getJobType(),
                'utm' => array(
                    'medium'   => Mailgun::UTM_MEDIUM,
                    'campaign' => $campaignId, // Using the same campaignId as Mailgun
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

    private function sendEmail($subject, $html, User $user, $campaignId, $deliveryTime)
    {
        $mailgun = $this->getContainer()->get('mailgun');

        $email = $user->getEmail();
        $env = $this->getContainer()->getParameter('kernel.environment');
        if($env !== 'prod')
        {
            $email = $this->getContainer()->getParameter('test_email');
        }

        try {
            $response = $mailgun->sendMessage( array(
                'from' => '"Class Central" <no-reply@class-central.com>',
                'to' => $email,
                'subject' => $subject,
                'html' => $html,
                'o:tag' => $campaignId,
                'o:deliverytime' => $deliveryTime
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
        } catch (\Exception $e)
        {
            // Probably a email validation error
            // Failed
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                'Mailgun Exception'
            );
        }

        return SchedulerJobStatus::getStatusObject(SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS, "Email sent");
    }
}