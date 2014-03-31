<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/30/14
 * Time: 7:01 PM
 */

namespace ClassCentral\ElasticSearchBundle\Types;

use Symfony\Component\DependencyInjection\ContainerInterface;


abstract class DocumentType {

    protected  $entity;

    protected  $container;

    public function __construct($entity, ContainerInterface $container )
    {
        $this->entity = $entity;
        $this->container = $container;
    }

    /**
     * Retuns a string that represents the type of
     * @return mixed
     */
    abstract public function getType();

    /**
     * Returns the id for the document
     * @return mixed
     */
    abstract public function getId();

    abstract public function getBody();

    /**
     * Retrieves the mapping for a particular type.
     * @return mixed
     */
    abstract public function getMapping();

    public function getDocument($indexName)
    {
        $doc = array();
        $doc['body'] = $this->getBody();
        $doc['id'] = $this->getId();
        $doc['type'] = $this->getType();
        $doc['index'] = $indexName;

        return $doc;
    }
} 