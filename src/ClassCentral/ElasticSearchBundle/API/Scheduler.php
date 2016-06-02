<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/19/14
 * Time: 5:49 PM
 */

namespace ClassCentral\ElasticSearchBundle\API;


use ClassCentral\ElasticSearchBundle\DocumentType\ESJobDocumentType;
use ClassCentral\ElasticSearchBundle\Scheduler\ESJob;

class Scheduler {

    private $indexName;
    private $esClient;

    public function __construct( $esClient,$indexName )
    {
        $this->indexName = $indexName;
        $this->esClient = $esClient;
    }

    /**
     * Checks whether a job exists or not
     * A Job is uniquely identified by three parameters
     * userId, type, and rundate
     * @param ESJob $job
     */
    public function jobExists( ESJob $job)
    {
        $params = array();
        $params['index'] = $this->indexName;
        $params['type'] = $this->getJobType();

        $date = $job->getRunDate()->format('Y-m-d');
        $query = array(
            'filtered' => array(
                'filter' => array(
                    'and' => array(
                        array(
                            "numeric_range" => array(
                                "runDate" => array(
                                    'gte' => $date,
                                    'lte' => $date
                                )
                            )
                        ),
                        array(
                            "term" => array(
                                "jobType" => $job->getJobType()
                            )
                        ),
                        array(
                            "term" => array(
                                "userId" => $job->getUserId()
                            )
                        )
                    )
                )
            )
        );



        $params['body']['query'] = $query;

        $results = $this->esClient->search( $params );

        $exists = ($results['hits']['total'] != 0);
        unset($results);

        return $exists;
    }

    public function findJobById( $id )
    {
        $params = array();
        $params['index'] = $this->indexName;
        $params['type'] = $this->getJobType();
        $params['id'] = $id;

        return $this->esClient->get( $params );
    }

    public function findJobsByDateAndType( $date, $type, $size = 10000)
    {
        $params = array();
        $params['index'] = $this->indexName;
        $params['type'] = $this->getJobType();

        $query = array(
            'filtered' => array(
                'filter' => array(
                    'and' => array(
                        array(
                            "numeric_range" => array(
                                "runDate" => array(
                                    'gte' => $date,
                                    'lte' => $date
                                )
                            )
                        ),
                        array(
                            "term" => array(
                                "jobType" => $type
                            )
                        )
                    )
                )
            )
        );

        $params['body']['query'] = $query;
        $params['body']['size'] = $size;

        $results = $this->esClient->search( $params );

        return $results;
    }

    /**
     * Deletes a job with a particular id
     * @param $id
     */
    public function delete ($id)
    {
        $params = array();
        $params['index'] = $this->indexName;
        $params['type'] = $this->getJobType();
        $params['id'] = $id;

        return $this->esClient->delete( $params );
    }

    protected function getJobType()
    {
        return 'job';
    }
} 