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
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Institution;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Entity\Stream;
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
        $pDoc = new ProviderDocumentType( new Initiative(), $this->container );
        $pMapping = $pDoc->getMapping();

        $iDoc = new InstitutionDocumentType( new Institution(), $this->container );
        $iMapping = $iDoc->getMapping();

        $sDoc = new SubjectDocumentType( new Stream(), $this->container) ;
        $sMapping = $sDoc->getMapping();

        return array(
            'provider' => array(
                'properties' => $pMapping
            ),
            'subjects' => array(
                "properties" => $sMapping
            ),
            'institutions' => array(
                "properties" => $iMapping
            )
        );
    }

    public function getBody()
    {
        $indexer = $this->container->get('es_indexer');
        $em = $this->container->get('doctrine')->getManager();
        $rs = $this->container->get('review');
        $body = array();
        $c = $this->entity ; // Alias for entity

        $body['name'] = $c->getName();
        $body['id'] = $c->getId();
        $body['videoIntro'] = $c->getVideoIntro();
        $body['length'] = $c->getLength();
        $body['slug'] = $c->getSlug();
        $body['description'] = $c->getDescription();
        $body['searchDesc'] = $c->getSearchDesc();
        $body['status'] = $c->getStatus();

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
        else
        {
            // Set the default to english
            $l = $em->getRepository('ClassCentralSiteBundle:Language')->findOneBy( array('slug'=> 'english' ) );
            $body['language']['name'] = $l->getName();
            $body['language']['id'] = $l->getId();
            $body['language']['slug'] = $l->getSlug();
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
           $provider = $c->getInitiative();
        }
        else
        {
            // create an independent provider
            $provider = new Initiative();
            $provider->setName('Independent');
            $provider->setCode('independent');
        }
        $pDoc = new ProviderDocumentType($provider, $this->container);
        $body['provider'] = $pDoc->getBody();

        // Get the next session
        $body['nextSession'] = array();
        $ns = CourseUtility::getNextSession($c);
        if($ns)
        {
            $nsDoc = new SessionDocumentType($ns,$this->container);
            $body['nextSession'] = $nsDoc->getBody();
        }

        // Subject
        $subjects = array();
        $sub = $c->getStream();
        if($sub->getParentStream())
        {
            // Add the parent stream first
            $psDoc = new SubjectDocumentType($sub->getParentStream(), $this->container);
            $subjects[] = $psDoc->getBody();
        }
        $sDoc = new SubjectDocumentType($sub, $this->container);
        $subjects[] = $sDoc->getBody();
        $body['subjects'] = $subjects;


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