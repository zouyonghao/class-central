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
use ClassCentral\SiteBundle\Utility\ReviewUtility;
use ClassCentral\SiteBundle\Utility\UniversalHelper;

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
            ),
            "subjectSlug" => array(
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

        $formatter = $c->getFormatter();

        $body['name'] = $c->getName();
        $body['id'] = $c->getId();
        $body['slug'] = $c->getSlug();
        $body['oneLiner'] = $c->getOneLiner();
        $body['subTitle'] = $c->getSubTitle();
        $body['price'] = $c->getPrice();
        $body['pricePeriod'] = $c->getPricePeriod();
        $body['displayPrice'] = $formatter->getPrice();
        $body['durationMin'] = $c->getDurationMin();
        $body['durationMax'] = $c->getDurationMax();
        $body['displayDuration'] = $formatter->getDuration();
        $body['workloadMin'] = $c->getWorkloadMin();
        $body['workloadMax'] = $c->getDurationMax();
        $body['workloadType'] = $c->getWorkloadType();
        $body['displayWorkload'] = $formatter->getWorkload();
        $body['url'] = $c->getUrl();
        $body['description'] = $c->getDescription();
        $body['syllabus'] = $c->getSyllabus();
        $body['status'] = $c->getStatus();
        $body['image'] = $credentialService->getImage( $c );
        $body['cardImage'] = $credentialService->getCardImage( $c );
        $body['buttonCTA'] = $formatter->getButtonCTA();

        $body['subjectSlug'] = null;
        $body['subject'] = null;

        if( $c->getSubject() )
        {
            $body['subjectSlug'] = $c->getSubject();
            $body['subject'] = Credential::$SUBJECTS[$c->getSubject()];
        }

        $orgs = array(); // Array of names of organizations who are involved in creating the credential

        // Provider
        $body['provider'] = array();
        if($c->getInitiative())
        {
            $provider = $c->getInitiative();

            $body['certificateName'] = $formatter->getCertificateName();
            $body['certificateSlug'] = $formatter->getCertificateSlug();
            $bulletOrg = "{$formatter->getCertificateName()} via ";

            $orgs[] = $provider->getName(); // Populate the organization list
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

        // Institutions
        $body['institutions'] = array();
        $institutions = array();
        foreach($c->getInstitutions() as $ins)
        {
            $iDoc = new InstitutionDocumentType($ins, $this->container);
            $body['institutions'][] = $iDoc->getBody();
            $orgs[] = $ins->getName(); // Populate the organization list
        }

        // Get the ratings
        $rating = $credentialService->calculateAverageRating( $this->entity );
        $body['rating'] = $rating['rating'];
        $body['formattedRating'] = ReviewUtility::formatRating( $rating['rating'] ); // Rounds the rating to the nearest 0.5
        $body['numRatings'] = $rating['numRatings'];

        $courses = array();
        foreach($this->entity->getCourses() as $course )
        {
            $cDoc = new CourseDocumentType( $course, $this->container );
            $courses[] = $cDoc->getBody();
        }
        $body['courses'] = $courses;

        $body['isSponsered'] = $c->isSponsored();

        // Build the bullet points in the array
        $bulletPoints = array();
        $bulletPoints[]  = $bulletOrg . UniversalHelper::commaSeparateList( $orgs ) ;

        // Bullet Price and Duration
        $bulletPriceAndDuration = $formatter->getPrice();
        $displayDuration = $formatter->getDuration();
        if( $displayDuration )
        {
            $bulletPriceAndDuration .= ' for ' . $displayDuration;
        }
        $bulletPoints[] = $bulletPriceAndDuration;

        // Bullet effort
        $effort = $formatter->getWorkload();
        if($effort)
        {
            $bulletPoints[] = $effort . ' of effort';
        }

        if( $provider->getName() == 'Coursera')
        {
            $bulletPoints[] = count($body['courses']) - 1 . ' courses + capstone project ';
        }
        elseif ($provider->getName() == 'HBX')
        {
            $bulletPoints[] = '3 courses and a final exam. Application Required';
        }


        if( $formatter->getEnrollment() )
        {
            $bulletPoints[] = $formatter->getEnrollment();
        }

        $body['bulletPoints'] =$bulletPoints;

        return $body;
    }


}