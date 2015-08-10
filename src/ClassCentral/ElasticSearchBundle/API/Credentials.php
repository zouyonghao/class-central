<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 7/24/15
 * Time: 11:20 PM
 */

namespace ClassCentral\ElasticSearchBundle\API;

/**
 * Credentials to provide
 * Class Credentials
 * @package ClassCentral\ElasticSearchBundle\API
 */
class Credentials {

    private $indexName;
    private $esClient;

    public function __construct( $esClient,$indexName )
    {
        $this->indexName = $indexName;
        $this->esClient = $esClient;
    }


    /**
     *
     * @param array $params specialization, nanodegree etc.
     * @return mixed
     */
    public function find( $queryFilters = array() )
    {
        $params = array();

        $params['index'] = $this->indexName;
        $params['type'] = 'credential';
        $params['body']['size'] = 1000;

        // Add Facets
        $params['body']['facets'] = array(
            "certificate" => array(
                'terms' => array(
                    'field' => 'certificateSlug',
                    'size' => 40
                )
            )
        );

        $params['body']['sort'] = array(
            "numRatings" => array(
                "order" => "desc"
            )
        );

        if( !empty($queryFilters['credentials']) )
        {
            $params['body']['filter'] = array(
                'and' => array(
                    array('terms' => array(
                        'certificateSlug' => $queryFilters['credentials'],
                        'execution' => 'or'
                    )),

                )
            );
        }
        $results = $this->esClient->search( $params );

        return $results;
    }

    /**
     * Returns a single credential
     * @param $slug
     */
    public function findBySlug( $slug )
    {
        $params = array();

        $params['index'] = $this->indexName;
        $params['type'] = 'credential';
        $params['body']['query']['constant_score']['filter'] = array(
                'term' => array(
                    'slug' => $slug
                )
        );

        $results = $this->esClient->search( $params );

        return $results;
    }
} 