<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/19/14
 * Time: 5:16 PM
 */

namespace ClassCentral\ElasticSearchBundle\Scheduler;


class ESRunner {

    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Run a job immediately by id
     * @param $id
     */
    public function runById($id)
    {
        $esScheduler = $this->container->get('es_scheduler');

        // Retrieve the job
        $result = $esScheduler->findJobById($id);
        $job = ESJob::getObjFromArray($result);

        $status = $this->run( $job );
    }

    /**
     * Run all the jobs for a particular date and type
     * @param $date
     * @param $type
     */
    public function runByDate( \DateTime $date, $type)
    {
        // Retrieve all jobs for this date and type
        $esScheduler = $this->container->get('es_scheduler');
        $indexer = $this->container->get('es_indexer');

        $results = $esScheduler->findJobsByDateAndType( $date->format('Y-m-d'), $type );

        foreach ($results['hits']['hits'] as $result)
        {
            $job = ESJob::getObjFromArray( $result );
            $status = $this->run( $job );

            // Create a log item
            $jl = ESJobLog::getJobLog( $job, $status );
            $indexer->index( $jl );

            $esScheduler->delete( $job->getId() );
        }
    }

    /**
     * Runs a particular job
     * @param ESJob $job
     * @return SchedulerJobStatus
     */
    public function run(ESJob $job)
    {
        $class = $job->getClass();

        // Check if the class exists
        if( !class_exists( $job->getClass() ))
        {
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_CLASS_NOT_FOUND,
                "The class $class has not been found");
        }

        $task = new $class();
        $task->setContainer( $this->container);

        $task->setUp();
        $status =  $task->perform( $job->getArgs() );
        $task->tearDown();

        return $status;
    }



} 