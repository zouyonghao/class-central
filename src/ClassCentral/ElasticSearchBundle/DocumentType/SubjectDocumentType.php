<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/3/14
 * Time: 10:37 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Types\DocumentType;
use ClassCentral\SiteBundle\Entity\Stream;

class SubjectDocumentType extends DocumentType {

    /**
     * Retuns a string that represents the type of
     * @return mixed
     */
    public function getType()
    {
        return 'subject';
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
        $s = $this->entity;
        $b = array();


        $b['id'] = $s->getId();
        $b['name'] = $s->getName();
        $b['slug'] = $s->getSlug();
        $b['imageUrl'] = $s->getImageUrl();
        $b['description'] = $s->getDescription();
        $b['displayOrder'] = $s->getDisplayOrder();
        $b['parent'] = '';
        if($s->getParentStream())
        {
            $b['parent'] = $s->getParentStream()->getSlug();
        }

        return $b;
    }

    /**
     * Retrieves the mapping for a particular type.
     * @return mixed
     */
    public function getMapping()
    {
        // TODO: Implement getMapping() method.
    }
}