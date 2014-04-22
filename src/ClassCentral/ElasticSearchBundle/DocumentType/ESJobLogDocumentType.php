<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/21/14
 * Time: 9:46 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Scheduler\ESJob;
use ClassCentral\ElasticSearchBundle\Scheduler\ESJobLog;
use ClassCentral\ElasticSearchBundle\Types\DocumentType;

class ESJobLogDocumentType extends DocumentType {

    /**
     * Retuns a string that represents the type of
     * @return mixed
     */
    public function getType()
    {
        return 'jobLog';
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
        $jobLog = $this->entity;
        $b = array();

        $b['id'] = $jobLog->getId();
        $b['created'] =  $jobLog->getCreated()->format('Y-m-d H:i:s');
        $b['status']['status'] = $jobLog->getStatus()->getStatus();
        $b['status']['message'] = $jobLog->getStatus()->getMessage();

        $jobDoc = new ESJobDocumentType( $jobLog->getJob(), $this->container);
        $b['job'] = $jobDoc->getBody();

        return $b;
    }

    /**
     * Retrieves the mapping for a particular type.
     * @return mixed
     */
    public function getMapping()
    {
        $jDoc = new ESJobDocumentType( new ESJob( 'fake_id '), $this->container );
        $mapping = $jDoc->getMapping();

        return array(
            'created' => array(
                'type' => 'date',
                'format' => 'YYYY-MM-dd HH:mm:ss'
            ),
            'job' => array(
                'properties' => $mapping
            )

        );
    }
}