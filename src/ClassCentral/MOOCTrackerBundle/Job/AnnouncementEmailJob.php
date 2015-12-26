<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 12/25/15
 * Time: 9:31 PM
 */

namespace ClassCentral\MOOCTrackerBundle\Job;


use ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobAbstract;

class AnnouncementEmailJob extends SchedulerJobAbstract {

    const ANNOUNCEMENT_EMAIL_JOB_TYPE = 'mt_announcement_email_job_type';

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