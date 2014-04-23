<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/21/14
 * Time: 8:12 PM
 */

namespace ClassCentral\ElasticSearchBundle\Scheduler;

/**
 * Holds the status of the job. Every job must return a status
 * Class SchedulerJobStatus
 * @package ClassCentral\ElasticSearchBundle\Scheduler
 */
class SchedulerJobStatus {

    const SCHEDULERJOB_STATUS_SUCCESS = 1;
    const SCHEDULERJOB_STATUS_FAILED  = 2;
    const SCHEDULERJOB_STATUS_CLASS_NOT_FOUND = 3;
    const SCHEDULERJOB_STATUS_JOB_NOT_FOUND = 4;

    public static $statuses = array(
        SchedulerJobStatus::SCHEDULERJOB_STATUS_SUCCESS => 'Success',
        SchedulerJobStatus::SCHEDULERJOB_STATUS_FAILED  => 'Failed',
        SchedulerJobStatus::SCHEDULERJOB_STATUS_CLASS_NOT_FOUND => 'Job class not found',
        SchedulerJobStatus::SCHEDULERJOB_STATUS_JOB_NOT_FOUND => 'Job not found'

    );

    public static function getStatusObject( $status, $message)
    {
        $js = new SchedulerJobStatus();
        $js->setStatus( $status );
        $js->setMessage( $message );
        return $js;
    }

    private $status;

    private $message;

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

} 