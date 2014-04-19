<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/18/14
 * Time: 8:57 PM
 */

namespace ClassCentral\ElasticSearchBundle\Scheduler;


class ESJob {

    private $id;

    /**
     * Job type
     * @var
     */
    private $type;

    /**
     * When the job was created
     * @var \DateTime
     */
    private $created;


    /**
     * Date to be run at
     * @var
     */
    private $runDate;

    /**
     * The class that will handle the job
     * @var
     */
    private $class;


    /**
     * Arguments for the job
     * @var
     */
    private $args;

    public function __construct( $id )
    {
       $this->created = new \DateTime();
    }

} 