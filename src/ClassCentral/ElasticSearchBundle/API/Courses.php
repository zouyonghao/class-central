<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/7/14
 * Time: 3:24 PM
 */

namespace ClassCentral\ElasticSearchBundle\API;


use ClassCentral\SiteBundle\Entity\CourseStatus;

class Courses {

    private $indexName;
    private $esClient;

    public function __construct( $esClient,$indexName )
    {
        $this->indexName = $indexName;
        $this->esClient = $esClient;
    }

    /**
     * Returns a list of all the courses for a particular provider
     * @param $slug
     */
    public function findByProvider( $slug )
    {
        if( $slug == 'others' )
        {
            // Special case
            $matchCriteria = array(
                'provider.navCode' => $slug
            );
        }
        else
        {
            $matchCriteria = array(
                'provider.code' => $slug
            );
        }



        return $this->findCourses($matchCriteria);
    }

    /**
     * Retrieves all the courses by a particular institution
     * @param $slug
     */
    public function findByInstitution( $slug )
    {
        $matchCriteria = array(
            'institutions.slug' => $slug
        );

        return $this->findCourses($matchCriteria);
    }

    /**
     * Retrieves all the courses by languages
     * @param $slug
     */
    public function findByLanguage( $slug )
    {
        $matchCriteria = array(
            'language.slug' => $slug
        );

        return $this->findCourses($matchCriteria);
    }

    /**
     * Find all the courses by time:
     * recent, upcoming, finished etc.
     * @param $status
     */
    public function findByTime ( $status )
    {
        $matchCriteria = array(
            'nextSession.states' => $status
        );

        return $this->findCourses($matchCriteria);
    }

    /**
     * @param $status
     */
    public function findBySubject( $id )
    {
        $matchCriteria = array(
            'subjects.id' => $id
        );

        return $this->findCourses($matchCriteria);
    }

    /**
     * Searches the term
     * @param $q
     */
    public function search ( $q )
    {
        $params = array();
        $qValues = $this->getDefaultQueryValues();

        $params['index'] = $this->indexName;
        $params['type'] = 'course';
        $params['body']['size'] = 1000;

        $query = array(
           "filtered" => array(
               "query" => array(
                    "multi_match" => array(
                        "query" => $q,
                        "type" => "best_fields",
                        "fields" => array(
                            'name^2',
                            'description',
                            'institutions.name',
                            'institutions.slug',
                            'instructors',
                            'provider.slug',
                            'provider.name',
                            "subjects.name",
                            "subject.slug",
                            "searchDesc"
                        ),
                        "tie_breaker" => 0.1,
                        "minimum_should_match" => "2<75%"

                    ),
               ),
               // Remove courses that ar not valid
               'filter' => $qValues['filter']
           )
        );

        $params['body']['query'] = $query;
        $params['body']['facets'] = $qValues['facets'];

        $results = $this->esClient->search($params);
        return $this->formatResults($results);
    }

    private function findCourses ( $matchCriteria )
    {
        $params = array();
        $qValues = $this->getDefaultQueryValues();

        $params['index'] = $this->indexName;
        $params['type'] = 'course';
        $params['body']['size'] = 1000;
        $query = array(
            'match' => $matchCriteria
        );


        $query = array(
            'filtered' => array(

                'query' => array(
                    'match' => $matchCriteria
                ),
                // Remove courses that ar not valid
                'filter' => $qValues['filter']
            )
        );

        $params['body']['sort'] = $qValues['sort'];
        $params['body']['query'] = $query;
        $params['body']['facets'] = $qValues['facets'];

        $results = $this->esClient->search($params);
        return $this->formatResults($results);
    }

    private function formatResults( $results )
    {

        $subjectIds = array();
        foreach($results['facets']['subjects']['terms'] as $term)
        {
            $subjectIds[] = $term['term'];
        }

        $languageIds = array();
        foreach($results['facets']['language']['terms'] as $term)
        {
            $languageIds[] = $term['term'];
        }

        $sessions = array();
        foreach( $results['facets']['sessions']['terms'] as $term )
        {
            $sessions[] = $term['term'];
        }
        return array(
            'subjectIds' => $subjectIds,
            'languageIds' => $languageIds,
            'sessions'    => $sessions,
            'results' => $results
        );
    }

    private function getDefaultQueryValues()
    {
        $sort = array(
            "nextSession.state" => array(
                "order" => "desc"
            ),
            "nextSession.startDate" => array(
                "order" => "asc"
            )
        );
        $facets = array(
            "subjects" => array(
                'terms' => array(
                    'field' => 'subjects.id',
                    'size' => 40
                )
            ),
            "language" => array(
                'terms' => array(
                    'field' => 'language.id',
                    'size' => 40
                )
            ),
            "sessions" => array(
                "terms" => array(
                    'field' => 'nextSession.states',
                    'size' => 10
                )
            ),
        );

        $filter = array(
            "range" => array(
                'status' => array(
                    "lte" => 100
                )
        ));

        return compact('sort','facets', 'filter');
    }

    /**
     * Get the counts for different items
     *  - providers
     *  - courses
     *  - subjects
     * @return array
     */
    public function getCounts()
    {
        $params = array();
        $qValues = $this->getDefaultQueryValues();
        $facets = $qValues['facets'];

        $params['index'] = $this->indexName;
        $params['type'] = 'course';
        $params['body']['size'] = 1;


        $query = array(
            'filtered' => array(

                'query' => array(
                    'match_all' => array()
                ),
                // Remove courses that ar not valid
                'filter' => $qValues['filter']
            )
        );

        // Add provider and providerNav counts to the facets
        $facets['provider'] = array(
            "terms" => array(
                'field' => 'provider.code',
                'size' => 1000
            )
        );

        $facets['providerNav'] = array(
            "terms" => array(
                'field' => 'provider.navCode',
                'size' => 1000
            )
        );

        $params['body']['query'] = $query;
        $params['body']['facets'] = $facets;

        $results = $this->esClient->search($params);

        $subjects = array();
        foreach($results['facets']['subjects']['terms'] as $term)
        {
            $subjects[ $term['term'] ] = $term['count'];
        }

        $languages = array();
        foreach($results['facets']['language']['terms'] as $term)
        {
            $languages[ $term['term'] ] = $term['count'];
        }

        $sessions = array();
        foreach( $results['facets']['sessions']['terms'] as $term )
        {
            $sessions[ $term['term'] ] = $term['count'] ;
        }

        $providers = array();
        foreach ($results['facets']['provider']['terms'] as $term)
        {
            $providers[ $term['term'] ] = $term['count'];
        }

        $providersNav = array();
        foreach ($results['facets']['providerNav']['terms'] as $term)
        {
            $providersNav[ $term['term'] ] = $term['count'];
        }

        return compact( 'subjects', 'languages', 'sessions', 'providers', 'providersNav');
    }


} 