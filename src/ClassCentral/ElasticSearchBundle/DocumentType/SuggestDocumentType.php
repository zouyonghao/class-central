<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 7/14/14
 * Time: 12:01 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Types\DocumentType;
use ClassCentral\SiteBundle\Controller\StreamController;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Stream;
use ClassCentral\SiteBundle\Utility\CourseUtility;

/**
 * This document is used to display the results for autocomplete box
 * Class SuggestDocumentType
 * @package ClassCentral\ElasticSearchBundle\DocumentType
 */
class SuggestDocumentType extends DocumentType{

    /**
     * Retuns a string that represents the type of
     * @return mixed
     */
    public function getType()
    {
        return 'suggest';
    }

    /**
     * Returns the id for the document
     * @return mixed
     */
    public function getId()
    {
       return get_class($this->entity) . '_' . $this->entity->getId();
    }

    public function getBody()
    {
        $indexer = $this->container->get('es_indexer');
        $em = $this->container->get('doctrine')->getManager();
        $rs = $this->container->get('review');
        $cache = $this->container->get('cache');
        $router = $this->container->get('router');
        $entity = $this->entity ;

        $body = array();
        $payload = array(); // contains data that would be useful for the frontend to format results
        if($this->entity instanceof Course)
        {
            $payload['type'] = 'course';
            $body['name_suggest']['input'] = array_merge(array($entity->getName()), $this->tokenize( $entity->getName() )) ;
            $body['name_suggest']['output'] = $entity->getName();

            // Boost the scores of upcoming, recent courses
            $weight = $this->getCourseWeight( $entity );
            if($weight > 0)
            {
                $body['name_suggest']['weight'] = $weight;
            }
            $payload['rating'] = $rs->calculateRatings($entity->getId());
            $rArray = $rs->getReviewsArray($entity->getId());
            $payload['reviewsCount'] = $rArray['count'];
            $payload['name'] = $entity->getName();
            $payload['nextSession'] = '';
            $ns = CourseUtility::getNextSession( $entity );
            if($ns)
            {
                $payload['nextSession'] = $ns->getDisplayDate();
            }
            // $provider
            $provider = 'Independent';
            if($entity->getInitiative())
            {
                $provider = $entity->getInitiative()->getName();
            }
            $payload['provider'] = $provider;
            // Url
            $payload['url'] = $router->generate('ClassCentralSiteBundle_mooc', array('id' => $entity->getId(), 'slug' => $entity->getSlug()));
            $body['name_suggest']['payload'] = $payload;
        }

        // Subjects
        if($this->entity instanceof Stream)
        {

            $payload['type'] = 'subject';
            $body['name_suggest']['input'] =  array_merge(array($entity->getName()), $this->tokenize( $entity->getName() )) ;
            $body['name_suggest']['output'] = $entity->getName();
            $body['name_suggest']['weight'] = 20;

            $this->tokenize( $entity->getName() );

            $payload['name'] = $entity->getName();
            $payload['count'] = $entity->getCourseCount();
            // Url
            $payload['url'] = $router->generate('ClassCentralSiteBundle_stream', array('slug' => $entity->getSlug()));
            $body['name_suggest']['payload'] = $payload;
        }

        return $body;
    }

    /**
     * Retrieves the mapping for a particular type.
     * @return mixed
     */
    public function getMapping()
    {
        return array(
            "name_suggest" => array(
                "type" => "completion",
                "payloads" => true,
                "index_analyzer" => "standard",
                "search_analyzer" => "standard",
                "preserve_position_increments"=> false,
                "preserve_separators"=> false
            )
        );
    }

    private function tokenize( $text )
    {
        $result = preg_split('/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter( $result, function($str){
            return (strlen($str) > 3) && (bool) preg_match('//u', $str);
        });
    }

    /**
     * Boost the weight of courses that are upcoming or recent
     * @param Course $course
     */
    private function getCourseWeight(Course $course)
    {
        $weight = 0;
        $ns = CourseUtility::getNextSession( $course );
        if($ns) {
            $states = CourseUtility::getStates($ns);
            $date = $ns->getStartDate();
            if( in_array('recent',$states) )
            {
                return 25;
            }

            if ( in_array( 'upcoming', $states) )
            {
                $dt = new \DateTime();
                $dt->add(new \DateInterval('P30D'));

                if( $date < $dt)
                {
                    return 15;
                }

                // Upcoming but later than a month
                return 10;
            }

            if( in_array('selfpaced', $states) )
            {
                return 14;
            }

        }

        return $weight;
    }
}