<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/21/14
 * Time: 8:03 PM
 */

namespace ClassCentral\ElasticSearchBundle\Scheduler;


/**
 * All jobs need to implement this interface
 * Class SchedulerJobAbstract
 * @package ClassCentral\ElasticSearchBundle\Scheduler
 */
abstract class SchedulerJobAbstract {

    private  $container;

    private $job;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function setJob(ESJob $job)
    {
        $this->job = $job;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getJob()
    {
        return $this->job;
    }

    abstract public function setUp();

    /**
     * Must return an object of type SchedulerJobStatus
     * @param $args
     * @return SchedulerJobStatus
     */
    abstract public function perform( $args );

    abstract public function tearDown();
}