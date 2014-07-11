<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/7/14
 * Time: 3:24 PM
 */

namespace ClassCentral\ElasticSearchBundle\API;


use ClassCentral\SiteBundle\Entity\CourseStatus;
use ClassCentral\SiteBundle\Entity\Offering;

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
            'institutions.slug' => strtolower($slug)
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

    public function findByTag( $tag )
    {
        $matchCriteria = array(
            'tags' => strtolower($tag)
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
     * Return courses between a specified start and end date
     * @param $start
     * @param $end
     * @return array
     */
    public function findByNextSessionStartDate( \DateTime $start, \DateTime $end )
    {
        $query = array(
            'bool' => array(
                'must' => array(
                    array(
                        'range' => array(
                            "nextSession.startDate" => array(
                                "gte" => $start->format('Y-m-d'),
                                "lte" => $end->format('Y-m-d')
                            )
                        )),
                    array(
                        'term' => array(
                            'nextSession.status' => Offering::START_DATES_KNOWN
                        )
                    )
                )
            )

        );

        return $this->findCourses( $query );
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


    public function findByIds( $courseIds )
    {
        $params = array();
        $qValues = $this->getDefaultQueryValues();

        $params['index'] = $this->indexName;
        $params['type'] = 'course';
        $params['body']['size'] = 1000;

        $filters = array(
            "terms" => array(
                'id' => $courseIds
            ),
            "range" => $qValues['filter']['range']
        );

        $qValues['filter']['terms'] = array(
            'ids' => $courseIds
        );

        $query = array(
            'filtered' => array(
                // Remove courses that ar not valid
                'filter' => array(
                    'and' => array(
                        array(
                            "terms" => array(
                                'id' => $courseIds
                            )
                        ),
                        array(
                            'range' => $qValues['filter']['range']
                        )

                    )
                )
            )
        );

        $params['body']['sort'] = $qValues['sort'];
        $params['body']['query'] = $query;
        $params['body']['facets'] = $qValues['facets'];

        //var_dump( json_encode($params['body'])); exit();

        $results = $this->esClient->search($params);
        return $this->formatResults($results);
    }

    /**
     * Searches the term
     * @param $q
     */
    public function search ( $q, $sessions = array() )
    {
        $params = array();
        $qValues = $this->getDefaultQueryValues();

        $params['index'] = $this->indexName;
        $params['type'] = 'course';
        $params['body']['size'] = 1000;

        if( $sessions )
        {
            $filter = array(
                'and' => array(
                    array(
                        "terms" => array(
                            'nextSession.states' => $sessions,
                            'execution' => 'and'
                        )
                    ),
                    array(
                        'range' => $qValues['filter']['range']
                    )
                )
            );
        }
        else
        {
            $filter = $qValues['filter'];
        }


        $query = array(
           "filtered" => array(
               "query" => array(
                    "multi_match" => array(
                        "query" => $q,
                        "type" => "best_fields",
                        "fields" => array(
                            'name^2',
                            'description',
                            'longDescription',
                            'syllabus',
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
               'filter' => $filter
           )
        );

        $params['body']['query'] = $query;
        $params['body']['facets'] = $qValues['facets'];

        $results = $this->esClient->search($params);
        return $this->formatResults($results);
    }


    private function findCourses ( $criteria )
    {
        $params = array();
        $qValues = $this->getDefaultQueryValues();

        $params['index'] = $this->indexName;
        $params['type'] = 'course';
        $params['body']['size'] = 1000;

        if( isset($criteria['range']) || isset( $criteria['bool'] ) )
        {
            $query = array(
                $criteria
            );
        }
        else
        {
            $query = array(
                'match' => $criteria
            );
        }



        $query = array(
            'filtered' => array(

                'query' => $query,
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
                    "lt" => 100
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


    public function getInstitutionCounts( $isUniversity = true)
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
                    'match' => array(
                        'institutions.isUniversity' => $isUniversity
                    )
                ),
                // Remove courses that ar not valid
                'filter' => $qValues['filter']
            )
        );

        // Add provider and providerNav counts to the facets
        $facets['institutions'] = array(
            "terms" => array(
                'field' => 'institutions.slug',
                'size' => 1000
            )
        );

        $params['body']['query'] = $query;
        $params['body']['facets'] = $facets;

//       var_dump( json_encode($params['body']) ); exit();
        $results = $this->esClient->search($params);

        $institutions = array();
        foreach ($results['facets']['institutions']['terms'] as $term)
        {
            $institutions[ $term['term'] ] = $term['count'];
        }

        return compact( 'institutions');
    }

} 