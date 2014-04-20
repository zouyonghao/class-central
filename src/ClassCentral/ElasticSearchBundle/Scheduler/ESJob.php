<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/18/14
 * Time: 8:57 PM
 */

namespace ClassCentral\ElasticSearchBundle\Scheduler;

/**
 *  Job definition
 * Class ESJob
 * @package ClassCentral\ElasticSearchBundle\Scheduler
 */
class ESJob {

    private $id;

    /**
     * Job type
     * @var
     */
    private $type;

    /**
     *
     * When the job was created
     * @var \DateTime
     */
    private $created;


    /**
     * Format - YYYY-MM-dd; Y-m-d in php
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
       $this->id = $id;
    }

    /**
     * @param mixed $args
     */
    public function setArgs($args)
    {
        $this->args = $args;
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $runDate
     */
    public function setRunDate($runDate)
    {
        $this->runDate = $runDate;
    }

    /**
     * @return mixed
     */
    public function getRunDate()
    {
        return $this->runDate;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }


} 