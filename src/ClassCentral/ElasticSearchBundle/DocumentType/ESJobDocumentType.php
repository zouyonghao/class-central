<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/19/14
 * Time: 4:25 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Scheduler\ESJob;
use ClassCentral\ElasticSearchBundle\Types\DocumentType;

class ESJobDocumentType extends DocumentType{

    /**
     * Retuns a string that represents the type of
     * @return mixed
     */
    public function getType()
    {
        return 'job';
    }

    /**
     * Returns the id for the document
     * @return mixed
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    public function getBody()
    {
        $job = $this->entity;
        $b = array();

        $b['id'] = $job->getId();
        $b['created'] = $job->getCreated()->format('Y-m-d H:i:s');
        $b['runDate'] = $job->getRunDate()->format('Y-m-d');
        $b['class'] = $job->getClass();
        $b['jobType'] = $job->getJobType();
        // Stringy the args since it will have different number of parameters.
        // The filed type would be string
        $b['args'] = json_encode($job->getArgs());
        $b['userId'] = $job->getUserId();

        return $b;
    }

    /**
     * Retrieves the mapping for a particular type.
     * @return mixed
     */
    public function getMapping()
    {
        return array(
            'created' => array(
                'type' => 'date',
                'format' => 'YYYY-MM-dd HH:mm:ss'
            ),
            'runDate' => array(
                'type' => 'date',
                'format' => 'YYYY-MM-dd'
            ),
            'jobType' => array(
                'type' => 'string',
                'format' => 'not_analyzed'
            ),
            'args' => array(
                'type' => 'string',
                'index' => 'not_analyzed'
            )
        );
    }
}