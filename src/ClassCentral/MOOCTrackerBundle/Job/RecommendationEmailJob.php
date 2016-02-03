<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/27/16
 * Time: 2:03 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;
use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus;
use ClassCentral\SiteBundle\Entity\User as UserEntity;
use ClassCentral\SiteBundle\Entity\UserPreference;
use ClassCentral\SiteBundle\Services\Mailgun;
use ClassCentral\SiteBundle\Utility\CryptUtility;

/**
 * Sends courses recommendations with follows
 * Class RecommendationEmailJob
 * @package ClassCentral\MOOCTrackerBundle\Job
 */
class RecommendationEmailJob extends SchedulerJobAbstract
{
    const RECOMMENDATION_EMAIL_JOB_TYPE = 'mt_recommendation_email_job_type';

    public function setUp()
    {
        // TODO: Implement setUp() method.
    }

    public function perform($args)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $suggestionsService = $this->getContainer()->get('suggestions');
        $userId = $this->getJob()->getUserId();
        $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneBy( array( 'id' => $userId) );

        if(!$user)
        {
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "User with id $userId not found for job " . self::RECOMMENDATION_EMAIL_JOB_TYPE
            );
        }

        $m = $args['month'];
        $y = $args['year'];
        $startDate = new \DateTime("$y-$m-1");
        $lastDayOfTheMonth = $startDate->format('t');
        $endDate = new \DateTime("$y-$m-$lastDayOfTheMonth");
        $data = $suggestionsService->byStartDate($user,$startDate,$endDate);

        if( count($data['courses']['hits']['hits']) == 0 )
        {
            // No courses found. Don't send email
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS,
                "User with id $userId has no course recommendations for job" . self::RECOMMENDATION_EMAIL_JOB_TYPE
            );

        }

        $emailContent = $this->getHTML($user,$data['courses'],$args['campaignId'], $startDate);

        return $this->sendEmail(
            $emailContent,
            $user,
            $args['campaignId'],
            $args['deliveryTime']
        );

    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }

    public function getHTML(UserEntity $user, $courses,$campaignId, $startDate)
    {
        $templating = $this->getContainer()->get('templating');
        $html = $templating->renderResponse(
            'ClassCentralMOOCTrackerBundle:Recommendation:recommendation.inlined.html',array(
                'user'   => $user,
                'courses' => $courses,
                'recommendationsPageUnlocked' => ( count($user->getFollows()) >= 10),
                'recommendationsMonth' => $startDate->format('F'),
                'loginToken' => $this->getContainer()->get('user_service')->getLoginToken($user),
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
    private function sendEmail( $html, UserEntity $user, $campaignId, $deliveryTime)
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
                'subject' => 'Recommendations for You',
                'html' => $html,
                'o:campaign' => $campaignId,
               // 'o:deliverytime' => $deliveryTime
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

        return SchedulerJobStatus::getStatusObject(SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS,
            "Email sent for job " . self::RECOMMENDATION_EMAIL_JOB_TYPE . " with user ". $user->getId()
        );
    }
}