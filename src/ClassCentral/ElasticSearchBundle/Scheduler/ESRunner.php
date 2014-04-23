<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/19/14
 * Time: 5:16 PM
 */

namespace ClassCentral\ElasticSearchBundle\Scheduler;


use Symfony\Component\Config\Definition\Exception\Exception;

class ESRunner {

    private $container;

    private $logger;

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
        $logger = $this->getLogger();
        $esScheduler = $this->container->get('es_scheduler');
        $indexer = $this->container->get('es_indexer');

        // Retrieve the job
        try
        {
            $result = $esScheduler->findJobById($id);
            $job = ESJob::getObjFromArray($result);

            $status = $this->run( $job );
            // Create a log item
            $jl = ESJobLog::getJobLog( $job, $status );
            $indexer->index( $jl );

            return $status;
        }
        catch (\Exception $e)
        {
            var_dump( $e->getMessage() );
            // Job not found
            $logger->error("RUNNER: runById - Job not found for id $id", array(
                'message' => $e->getMessage()
            ));

            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_JOB_NOT_FOUND,
                "Job with $id not found"
            );
        }
    }

    /**
     * Run all the jobs for a particular date and type
     * @param $date
     * @param $type
     */
    public function runByDate( \DateTime $date, $type)
    {
        $logger = $this->getLogger();
        $esScheduler = $this->container->get('es_scheduler');
        $indexer = $this->container->get('es_indexer');

        $dt = $date->format('Y-m-d');


        $logger->info("RUNNER: runByDate called with parameters date $dt & type $type");

        // Retrieve all jobs for this date and type
        $results = $esScheduler->findJobsByDateAndType( $date->format('Y-m-d'), $type );

        $totalJobs = $results['hits']['total'];
        $logger->info("RUNNER: $totalJobs jobs found");

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
        $logger = $this->getLogger();
        $class = $job->getClass();
        $jobId = $job->getId();

        $logger->info("RUNNER: Running job with id $jobId");

        // Check if the class exists
        if( !class_exists( $job->getClass() ))
        {

            $logger->error(
                "Runner: Class $class not found for job with id $jobId",
                ESJob::getArrayFromObj( $job)
            );
            return SchedulerJobStatus::getStatusObject(
                SchedulerJobStatus::SCHEDULERJOB_STATUS_CLASS_NOT_FOUND,
                "The class $class has not been found");
        }

        $task = new $class();
        $task->setContainer( $this->container);

        $task->setUp();
        $status =  $task->perform( $job->getArgs() );
        $task->tearDown();

        $logger->info(
            "RUNNER: Job with id $jobId completed", array(
                'status' => SchedulerJobStatus::$statuses[ $status->getStatus()],
                'message' => $status->getMessage()
            )
        );

        return $status;
    }

    private function getLogger()
    {
        if(!$this->logger)
        {
            $this->logger = $this->container->get('monolog.logger.scheduler');
        }
        return $this->logger;
    }

} 