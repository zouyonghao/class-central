<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/21/14
 * Time: 9:19 PM
 */

namespace ClassCentral\ElasticSearchBundle\Scheduler;


/***
 * This class is used to test the scheduler
 * Class DummyJob
 * @package ClassCentral\ElasticSearchBundle\Scheduler
 */
class DummyJob extends SchedulerJobAbstract {

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
        return SchedulerJobStatus::getStatusObject( SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS, "Dummy job - Successful"  );
    }


    public function tearDown()
    {
        // TODO: Implement tearDown() method.
    }
}