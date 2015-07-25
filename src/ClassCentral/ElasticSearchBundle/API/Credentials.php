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


    public function find()
    {
        $params = array();

        $params['index'] = $this->indexName;
        $params['type'] = 'credential';
        $params['body']['size'] = 1000;

        $results = $this->esClient->search( $params );

        return $results;
    }
} 