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
        $indexer = $this->container->get('es_indexer');
        $body = array();
        $c = $this->entity ; // Alias for entity

        $body['name'] = $c->getName();
        $body['id'] = $c->getId();
        $body['videoIntro'] = $c->getVideoIntro();
        $body['length'] = $c->getLength();
        $body['slug'] = $c->getSlug();

        // Instructors
        $body['instructors'] = array();
        foreach($c->getInstructors() as $instructor)
        {
            $body['instructors'][] = $instructor->getName();
        }

        // Language
        $body['language'] = array();
        $lang = $c->getLanguage();
        if($lang)
        {
            $course['language']['name'] = $lang->getName();
            $course['language']['id'] = $lang->getId();
            $course['language']['slug'] = $lang->getSlug();
        }

        // Institutions
        $body['institutions'] = array();
        foreach($c->getInstitutions() as $ins)
        {
            $iDoc = new InstitutionDocumentType($ins, $this->container);
            $body['institutions'][] = $iDoc->getBody();
        }


        // Provider
        $body['provider'] = array();
        if($c->getInitiative())
        {
            $pDoc = new ProviderDocumentType($c->getInitiative(), $this->container);
            $body['provider'] = $pDoc->getBody();
        }


        return $body;
    }


} 