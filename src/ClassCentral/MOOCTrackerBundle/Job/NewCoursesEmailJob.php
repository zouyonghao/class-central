<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 2/14/16
 * Time: 3:41 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;
use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus;
use ClassCentral\SiteBundle\Entity\UserPreference;
use ClassCentral\SiteBundle\Services\Mailgun;
use ClassCentral\SiteBundle\Entity\User as UserEntity;
use ClassCentral\SiteBundle\Utility\CryptUtility;

class NewCoursesEmailJob extends SchedulerJobAbstract
{
    const NEW_COURSES_EMAIL_JOB_TYPE = 'mt_new_courses_email_job_type';

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
        $suggestionsService = $this->getContainer()->get('suggestions');
        $userId = $this->getJob()->getUserId();
        $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneBy( array( 'id' => $userId) );

        if(!$user)
        {
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED,
                "User with id $userId not found for job " . self::NEW_COURSES_EMAIL_JOB_TYPE
            );
        }

        $data = $suggestionsService->newCoursesbyUser($user,31);

        if( count($data['courses']['hits']['hits']) == 0 )
        {
            // No courses found. Don't send email
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS,
                "User with id $userId has no course recommendations for job" . self::NEW_COURSES_EMAIL_JOB_TYPE
            );

        }

        $emailContent = $this->getHTML($user,$data['courses'],$args['campaignId']);

        return $this->sendEmail(
            $emailContent,
            $user,
            count($data['courses']['hits']['hits']),
            $args['campaignId'],
            $args['deliveryTime']
        );
    }

    public function getHTML(UserEntity $user, $courses,$campaignId)
    {
        $templating = $this->getContainer()->get('templating');
        $html = $templating->renderResponse(
            'ClassCentralMOOCTrackerBundle:NewCourses:newcourses.inlined.html',array(
                'user'   => $user,
                'courses' => $courses,
                'recommendationsPageUnlocked' => ( count($user->getFollows()) >= 10),
                'loginToken' => $this->getContainer()->get('user_service')->getLoginToken($user,false),
                'baseUrl' => $this->getContainer()->getParameter('baseurl'),
                'jobType' => $this->getJob()->getJobType(),
                'utm' => array(
                    'medium'   => Mailgun::UTM_MEDIUM,
                    'campaign' => $campaignId, // Using the same campaignId as Mailgun
                    'source'   => Mailgun::UTM_SOURCE_PRODUCT,
                ),
                'unsubscribeToken' => CryptUtility::getUnsubscribeToken( $user,
                    UserPreference::USER_PREFERENCE_PERSONALIZED_COURSE_RECOMMENDATIONS,
                    $this->getContainer()->getParameter('secret')
                )
            )
        )->getContent();

        return $html;
    }
    private function sendEmail( $html, UserEntity $user, $numCourses, $campaignId, $deliveryTime)
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
                'subject' => $numCourses . ' brand new courses for you to join',
                'html' => $html,
                'o:campaign' => $campaignId,
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
                'Mailgun Exception - ' . $e->getMessage()
            );
        }

        return SchedulerJobStatus::getStatusObject(SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS,
            "Email sent for job " . self::NEW_COURSES_EMAIL_JOB_TYPE . " with user ". $user->getId()
        );
    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }
}