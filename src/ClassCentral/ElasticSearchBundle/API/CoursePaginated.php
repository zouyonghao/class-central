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

    public function find( $query = array(), $filters = array(), $facets = array(), $sort = array(), $page = 1 )
    {
        $params = array();

        $params['index'] = $this->indexName;
        $params['type'] = 'course';

        if( $page == -1 )
        {
            $params['body']['size'] = 1000; // Increase the limit
        }
        else
        {
            $params['body']['size'] = self::PAGE_SIZE ;
            $params['body']['from'] = self::PAGE_SIZE * ($page - 1);

        }


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
        if( !empty($sort) )
        {
            $params['body']['sort'] = $sort;
        }

        //var_dump( json_encode( $params['body'])); exit();

        $results = $this->esClient->search($params);

        return $results;
    }




} 