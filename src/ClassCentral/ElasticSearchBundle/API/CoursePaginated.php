<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/16/14
 * Time: 11:53 AM
 */

namespace ClassCentral\ElasticSearchBundle\API;


class CoursePaginated {
    const PAGE_SIZE = 50;
    private $indexName;
    private $esClient;

    public function __construct( $esClient,$indexName )
    {
        $this->indexName = $indexName;
        $this->esClient = $esClient;
    }

    public function find( $query = array(), $filters = array(), $facets = array(), $offset = 0 )
    {
        $params = array();

        $params['index'] = $this->indexName;
        $params['type'] = 'course';
        $params['body']['size'] = self::PAGE_SIZE;

        $params['body']['query'] = $query;
        $params['body']['facets'] = $facets;
        $params['body']['filter'] = $filters;

        $results = $this->esClient->search($params);

        return $results;
    }


} 