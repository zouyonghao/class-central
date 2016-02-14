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
        // TODO: Implement perform() method.
    }

    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }
}