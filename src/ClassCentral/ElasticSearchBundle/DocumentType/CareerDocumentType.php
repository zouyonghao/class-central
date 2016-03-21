<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/21/16
 * Time: 10:33 AM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Types\DocumentType;

class CareerDocumentType extends DocumentType
{

    /**
     * Retuns a string that represents the type of
     * @return mixed
     */
    public function getType()
    {
        return 'career';
    }

    /**
     * Returns the id for the document
     * @return mixed
     */
    public function getId()
    {
       $this->entity->getId();
    }

    public function getBody()
    {
        $c = $this->entity;
        $b = array();

        $b['id'] = $c->getId();
        $b['name'] = $c->getName();
        $b['slug'] = $c->getSlug();

        return $b;
    }

    /**
     * Retrieves the mapping for a particular type.
     * @return mixed
     */
    public function getMapping()
    {
        return array(
            "slug" => array(
                "type" => "string",
                "index" => "not_analyzed"
            )
        );
    }
}