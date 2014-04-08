<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/7/14
 * Time: 3:24 PM
 */

namespace ClassCentral\ElasticSearchBundle\API;


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
        $matchCriteria = array(
           'provider.code' => $slug
        );

        return $this->findCourses($matchCriteria);
    }

    /**
     * Retrieves all the courses by a particular institution
     * @param $slug
     */
    public function findByInstitution( $slug )
    {

    }

    /**
     * Retrieves all the courses by languages
     * @param $slug
     */
    public function findByLanguage( $slug )
    {

    }

    /**
     * Find all the courses by time:
     * recent, upcoming, finished etc.
     * @param $status
     */
    public function findByTime ( $status )
    {

    }

    private function findCourses ( $matchCriteria )
    {
        $params = array();

        $params['index'] = $this->indexName;
        $params['type'] = 'course';
        $params['body']['size'] = 1000;


        $query = array(
            'match' => $matchCriteria
        );

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
            )
        );

        $params['body']['sort'] = $sort;
        $params['body']['query'] = $query;
        $params['body']['facets'] = $facets;



        $results = $this->esClient->search($params);

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

        return array(
            'subjectIds' => $subjectIds,
            'languageIds' => $languageIds,
            'results' => $results
        );
    }

} 