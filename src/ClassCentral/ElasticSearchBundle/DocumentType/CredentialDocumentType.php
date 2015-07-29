<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 7/23/15
 * Time: 11:53 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\CredentialBundle\Entity\Credential;
use ClassCentral\ElasticSearchBundle\Types\DocumentType;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Institution;

class CredentialDocumentType extends DocumentType {


    /**
     * Retuns a string that represents the type of
     * @return mixed
     */
    public function getType()
    {
        return 'credential';
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
        $pDoc = new ProviderDocumentType( new Initiative(), $this->container);
        $pMapping = $pDoc->getMapping();

        $iDoc = new InstitutionDocumentType(new Institution(), $this->container);
        $iMapping = $iDoc->getMapping();

        return array(
            'provider' => array(
                'properties' => $pMapping
            ),
            'institutions' => array(
                "properties" => $iMapping
            ),

            "name" => array(
                "type" => "string",
                "fields" => array(
                    "raw" => array(
                        "type" => "string",
                        "index" => 'not_analyzed'
                    )
                )
            ),

            "slug" => array(
                "type" => "string",
                'index' => 'not_analyzed',
            )

        );
    }

    public function getBody()
    {
        $body = array();
        $credentialService = $this->container->get('credential');
        $c = $this->entity ; // Alias for entity

        $body['name'] = $c->getName();
        $body['id'] = $c->getId();
        $body['slug'] = $c->getSlug();
        $body['oneLiner'] = $c->getOneLiner();
        $body['price'] = $c->getPrice();
        $body['pricePeriod'] = $c->getPricePeriod();
        $body['displayPrice'] = $c->getDisplayPrice();
        $body['durationMin'] = $c->getDurationMin();
        $body['durationMax'] = $c->getDurationMax();
        $body['workloadMin'] = $c->getWorkloadMin();
        $body['workloadMax'] = $c->getDurationMax();
        $body['workloadType'] = $c->getWorkloadType();
        $body['url'] = $c->getUrl();
        $body['description'] = $c->getDescription();
        $body['status'] = $c->getStatus();
        $body['image'] = $credentialService->getImage( $c );
        $body['cardImage'] = $credentialService->getCardImage( $c );

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

            // Certificate details
            $certDetails = \ClassCentral\CredentialBundle\Services\Credential::getCertificateDetails( $this->entity );
            if($certDetails)
            {
                $body['certificateName'] = $certDetails['name'];
                $body['certificateSlug'] = $certDetails['slug'];
            }
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

        // Get the ratings
        $rating = $credentialService->calculateAverageRating( $this->entity );
        $body['rating'] = $rating['rating'];
        $body['numRatings'] = $rating['numRatings'];

        $courses = array();
        foreach($this->entity->getCourses() as $course )
        {
            $cDoc = new CourseDocumentType( $course, $this->container );
            $courses[] = $cDoc->getBody();
        }
        $body['courses'] = $courses;


        // Build the bullet points in the array
        $bulletPoints = array();

        // Bullet 1
        $certDetails = $credentialService->getCertificateDetails($this->entity);
        $provider = $this->entity->getInitiative()->getName();
        $institutions = $this->entity->getInstitutions();
        $bullet1 = "{$certDetails['name']} via $provider";
        $bulletPoints[]  = $bullet1 ;

        // Bullet 2
        $bullet2 = $this->entity->getDisplayPrice();
        $bulletPoints[] = $bullet2;

        $body['bulletPoints'] =$bulletPoints;

        return $body;
    }


}