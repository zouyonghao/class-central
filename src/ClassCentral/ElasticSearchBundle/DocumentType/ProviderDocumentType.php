<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/31/14
 * Time: 4:20 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Types\DocumentType;
use ClassCentral\SiteBundle\Entity\Initiative;

class ProviderDocumentType extends DocumentType
{

    /**
     * Retuns a string that represents the type of
     * @return mixed
     */
    public function getType()
    {
        return 'provider';
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
        $b  = array();
        $p = $this->entity;
        $b['id'] = $p->getId();
        $b['name'] = $p->getName();
        $b['url'] = $p->getUrl();
        $b['imageUrl'] = $p->getImageUrl();
        $b['code'] = strtolower($p->getCode());
        $b['description'] = $p->getDescription();
        $b['tooltip'] = $p->getTooltip();


        // To get navbar counts and courses
        if( !in_array( $p->getCode(), Initiative::$types) )
        {
            $b['navCode'] = 'others';
        }
        else
        {
            $b['navCode'] = strtolower($p->getCode());
        }

        return $b;
    }

    /**
     * Retrieves the mapping for a particular type.
     * @return mixed
     */
    public function getMapping()
    {
        return array(
            'code' => array(
                "type" => "string",
                "index" => "not_analyzed"
            ),
            'navCode' => array(
                "type" => "string",
                "index" => "not_analyzed"
            )
        );
    }
}