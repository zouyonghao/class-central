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
    public function schedule( \DateTime $date, $type, $class, $arguments = array() )
    {
        $indexer = $this->container->get('es_indexer');

        $id = md5(uniqid('', true));
        $job = new ESJob( $id );
        $job->setRunDate($date);
        $job->setClass($class);
        $job->setArgs($arguments);
        $job->setType( $type );

        $indexer->index( $job );

        return $id;
    }

} 