<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/30/14
 * Time: 7:23 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Types\DocumentType;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Utility\CourseUtility;
use ClassCentral\SiteBundle\Utility\ReviewUtility;

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
        $rs = $this->container->get('review');
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
            $body['language']['name'] = $lang->getName();
            $body['language']['id'] = $lang->getId();
            $body['language']['slug'] = $lang->getSlug();
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

        // Get the next session
        $body['nextSession'] = array();
        $ns = CourseUtility::getNextSession($c);
        if($ns)
        {
            $nsDoc = new SessionDocumentType($ns,$this->container);
            $body['nextSession'] = $nsDoc->getBody();
        }

        // Sessions. Add sessions to the records
        $sessions = array();
        $body['sessions'] = array();
        foreach( $c->getOfferings() as $session )
        {
            // Ignore invalid session
            if($session->getStatus()  == Offering::COURSE_NA)
            {
                continue;
            }

            $sDoc = new SessionDocumentType($session, $this->container);
            $sessions[] = $sDoc->getBody();
        }
        $body['sessions'] = $sessions;

        $body['rating'] = $rs->calculateRatings($c->getId());
        $rArray = $rs->getReviewsArray($c->getId());
        $body['reviewsCount'] = $rArray['count'];


        return $body;
    }


} 