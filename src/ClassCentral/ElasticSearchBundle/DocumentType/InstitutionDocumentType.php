<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/31/14
 * Time: 3:47 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Types\DocumentType;
use ClassCentral\SiteBundle\Entity\Institution;

class InstitutionDocumentType extends DocumentType{


    /**
     * Returns a string that represents the type
     * @return mixed
     */
    public function getType()
    {
        return 'institution';
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
        $b = array(); // body
        $i = $this->entity; // Institution

        $b['id'] = $i->getId();
        $b['name'] = $i->getName();
        $b['url'] = $i->getUrl();
        $b['slug'] = $i->getSlug();
        $b['isUniversity'] = $i->getIsUniversity();
        $b['description'] = $i->getDescription();
        $b['imageUrl'] = $i->getImageUrl();

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