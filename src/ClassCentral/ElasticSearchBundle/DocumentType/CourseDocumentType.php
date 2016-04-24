<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/30/14
 * Time: 7:23 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Types\DocumentType;
use ClassCentral\SiteBundle\Entity\Career;
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

        // Next Session mapping for sorting
        $nsDoc = new SessionDocumentType( new Offering(), $this->container);
        $nsMapping= $nsDoc->getMapping();

        // Career mapping
        $cDoc = new CareerDocumentType(new Career(), $this->container);
        $cMapping = $cDoc->getMapping();

        return array(
            'provider' => array(
                'properties' => $pMapping
            ),
            'subjects' => array(
                "properties" => $sMapping
            ),
            'institutions' => array(
                "properties" => $iMapping
            ),
            'nextSession' => array(
                'properties' => $nsMapping
            ),
            'careers' => array(
                'properties' => $cMapping
            ),
            'tags' => array(
                'type' => "string",
                "index" => "not_analyzed"
            ),
            "name" => array(
                "type" => "string",
                "fields" => array(
                    "raw" => array(
                        "type" => "string",
                        "index" => 'not_analyzed'
                    )
                )
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
        $formatter = $c->getFormatter();

        $body['name'] = $c->getName();
        $body['isMOOC'] = $c->getIsMOOC();
        $body['id'] = $c->getId();
        $body['videoIntro'] = $c->getVideoIntro();
        $body['length'] = $c->getLength();
        $body['workloadType'] = $c->getWorkloadType();
        $body['workloadMin'] = $c->getWorkloadMin();
        $body['workloadMax'] = $c->getWorkloadMax();
        $body['slug'] = $c->getSlug();
        $body['description'] = $c->getDescription();
        $body['longDescription'] = $c->getLongDescription();
        $body['syllabus'] = $c->getSyllabus();
        $body['searchDesc'] = $c->getSearchDesc();
        $body['status'] = $c->getStatus();
        $body['certificate']  = $c->getCertificate();
        $body['certificatePrice'] = $c->getCertificatePrice();
        $body['certificateDisplay'] = $formatter->getCertificate();
        $body['verifiedCertificate']  = $c->getVerifiedCertificate();
        $body['durationMin'] = $c->getDurationMin();
        $body['durationMax'] = $c->getDurationMax();
        $body['price'] = $c->getPrice();
        $body['pricePeriod'] = $c->getPricePeriod();

        $body['url'] = $c->getUrl();
        if($c->getCreated())
        {
            $body['created'] = $c->getCreated()->format(DATE_ISO8601);
        }

        // Tags
        $tags = array();
        foreach($c->getTags() as $tag)
        {
            $tags[] = strtolower($tag->getName());
        }
        $body['tags'] = $tags;

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

        // Careers
        $careers = array();
        foreach($c->getCareers() as $career)
        {
            $cDoc = new CareerDocumentType( $career, $this->container );
            $careers[] = $cDoc->getBody();
        }
        $body['careers'] = $careers;

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
        $body['numSessions'] = count( $sessions );

        $body['rating'] = $rs->getRatings($c->getId());
        $body['ratingSort'] = $rs->getBayesianAverageRating( $c->getId() );
        $rArray = $rs->getReviewsArray($c->getId());
        $body['reviewsCount'] = $rArray['count'];
        $body['ratingStars'] = ReviewUtility::getRatingStars( $body['rating'] );
        $body['formattedRating'] = ReviewUtility::formatRating( $body['rating'] );

        // Get Followed count
        $courseRepo = $this->container->get('doctrine')
            ->getManager()->getRepository('ClassCentralSiteBundle:Course');
        $body['followed'] = intval($courseRepo->getListedCount($c));


        // Check if the course being offered is new
        // Definition of new - created 30 days ago
        $oneMonthAgo = new \DateTime();
        $oneMonthAgo->sub(new \DateInterval("P30D"));
        $newCourse = false;
        if($c->getCreated() >= $oneMonthAgo)
        {
            $newCourse = true;
        }


        $body['new'] = intval($newCourse);

        $startingSoon = false;
        $oneMonthLater = new \DateTime();
        $oneMonthLater->add(new \DateInterval("P30D"));
        if( $ns && !in_array('selfpaced', $body['nextSession']['states']) && in_array('upcoming', $body['nextSession']['states']))
        {
            if($ns->getStartDate() < $oneMonthLater and $ns->getStatus() != Offering::START_DATES_UNKNOWN)
            {
                $startingSoon = true;
            }
        }
        $body['startingSoon'] = intval($startingSoon);


        // Get the Credential
        $credential = array();
        if ( !$c->getCredentials()->isEmpty() )
        {
            $cred = $c->getCredentials()->first();
            if( $cred->getStatus() < 100 ) // Only if its approved
            {
                $credential['id'] = $cred->getId();
                $credential['name'] = $cred->getName();
                $credential['slug'] = $cred->getSlug();
                $credential['certificateName'] = '';
                $credential['certificateSlug'] = '';
                $credFormatter = $cred->getFormatter();
                $credential['certificateName'] = $credFormatter->getCertificateName();
                $credential['certificateSlug'] = $credFormatter->getCertificateSlug();
            }
        }
        $body['credential'] = $credential;
        return $body;
    }


} 