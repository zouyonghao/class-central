<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/21/14
 * Time: 9:41 PM
 */

namespace ClassCentral\ElasticSearchBundle\Scheduler;

/***
 * Log of all jobs that have been run
 * Class ESJobLog
 * @package ClassCentral\ElasticSearchBundle\Scheduler
 */
class ESJobLog {

    private $id;

    /**
     * The time which the job ran at
     * @var
     */
    private $created;

    /**
     * A copy of the entire job
     * @var ESJob
     */
    private $job;


    /**
     * @var SchedulerJobStatus
     */
    private $status;


    public function __construct()
    {
        $this->created = new \DateTime();
    }
    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \ClassCentral\ElasticSearchBundle\Scheduler\ESJob $job
     */
    public function setJob(ESJob $job)
    {
        $this->job = $job;
    }

    /**
     * @return \ClassCentral\ElasticSearchBundle\Scheduler\ESJob
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param \ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus $status
     */
    public function setStatus(SchedulerJobStatus $status)
    {
        $this->status = $status;
    }

    /**
     * @return \ClassCentral\ElasticSearchBundle\Scheduler\SchedulerJobStatus
     */
    public function getStatus()
    {
        return $this->status;
    }


    public static function getJobLog(ESJob $job, SchedulerJobStatus $status)
    {
        $jl = new ESJobLog();
        $jl->setId( md5(uniqid('', true)) );
        $jl->setStatus( $status );
        $jl->setJob( $job );

        return $jl;
    }


} 