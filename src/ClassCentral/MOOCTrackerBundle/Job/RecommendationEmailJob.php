<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/27/16
 * Time: 2:03 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;

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

    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }
}