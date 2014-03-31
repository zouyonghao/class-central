<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/30/14
 * Time: 7:23 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Types\DocumentType;

class CourseDocumentType extends DocumentType {
    /**
     * Retuns a string that represents the type of
     * @return mixed
     */
    public function getType()
    {
        return 'course';
    }

    /**
     * Returns the id for the document
     * @return mixed
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Retrieves the mapping for a particular type.
     * @return mixed
     */
    public function getMapping()
    {
        // TODO: Implement getMapping() method.
    }

    public function getBody()
    {
        $body = array();
        $c = $this->entity ; // Alias for entity

        $body['name'] = $c->getName();

        $provider = 'Independent';
        if($c->getInitiative())
        {
            $provider = $c->getInitiative()->getName();
        }
        $body['provider'] = $provider;

        return $body;
    }


} 