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
    }

    /**
     * Run all the jobs for a particular date and type
     * @param $date
     * @param $type
     */
    public function runByDate( $date, $type)
    {
        // Retrieive the jobs
    }

    public function run(ESJob $job)
    {

    }


} 