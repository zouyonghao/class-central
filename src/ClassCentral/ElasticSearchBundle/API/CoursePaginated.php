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

    public function find( $query = array(), $filters = array(), $facets = array(), $page = 1 )
    {
        $params = array();

        $params['index'] = $this->indexName;
        $params['type'] = 'course';
        $params['body']['size'] = self::PAGE_SIZE ;
        // TODO: Check this calculation
        //$params['body']['from'] = self::PAGE_SIZE * ($page - 1);

        $params['body']['query'] = array(
            'bool' => array(
                'must' => array(
                    array(
                        "range" => array(
                            'status' => array(
                                "lt" => 100
                            )
                        ),
                    ),
                    $query


            )

        ));
        $params['body']['facets'] = $facets;
        if( !empty($filters) )
        {
            $params['body']['filter'] = $filters;
        }

        //var_dump( json_encode( $params['body']));
        $results = $this->esClient->search($params);

        return $results;
    }


} 