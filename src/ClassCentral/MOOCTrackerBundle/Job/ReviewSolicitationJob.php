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

/**
 * Sends a email asking users to write reviews for courses that have been
 * added to MOOC Tracker. If the courses have been rated then ask for
 * detailed reviews
 * Class ReviewSolicitationJob
 * @package ClassCentral\MOOCTrackerBundle\Job
 */
class ReviewSolicitationJob extends SchedulerJobAbstract {

    const REVIEW_SOLICITATION_JOB_TYPE = 'mt_ask_for_reviews_for_completed_courses';
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