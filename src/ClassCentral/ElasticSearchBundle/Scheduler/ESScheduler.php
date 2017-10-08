<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/19/14
 * Time: 4:51 PM
 */

namespace ClassCentral\ElasticSearchBundle\Scheduler;


use ClassCentral\ElasticSearchBundle\DocumentType\ESJobDocumentType;

class ESScheduler {

    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Saves the job in elastic search
     * @param \DateTime $date
     * @param $class
     * @param $arguments
     */
    public function schedule( \DateTime $date, $type, $class, $arguments = array(), $userId = -1, $split = 0)
    {
        $logger = $this->container->get('monolog.logger.scheduler');
        $indexer = $this->container->get('es_indexer');
        $esScheduler = $this->container->get('es_scheduler');


        $id = md5(uniqid('', true));
        $splitId = 0;
        if($split >=2 )
        {
            $splitId = crc32($id)%$split;
        }
        $job = new ESJob( $id );
        $job->setRunDate($date);
        $job->setClass($class);
        $job->setArgs($arguments);
        $job->setJobType( $type );
        $job->setUserId( $userId );
        $job->setSplitId( $splitId );

        // Check if the job already exists
        if ($esScheduler->jobExists( $job ) )
        {
            $logger->info( "SCHEDULER :  job already exists", ESJob::getArrayFromObj( $job ) );
            return false;
        }
        else
        {
            $indexer->index( $job );
            $logger->info( "SCHEDULER :  job created with id $id", ESJob::getArrayFromObj( $job ) );
            return $id;
        }

    }

} 