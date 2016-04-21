<?php

namespace ClassCentral\ClassCentralSiteBundle\Formatters;

/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/21/16
 * Time: 12:04 AM
 */
abstract class CourseFormatterAbstract
{
    protected $course = null;

    public function __construct(\ClassCentral\SiteBundle\Entity\Course $course)
    {
        $this->course = $course;
    }

    abstract public function getPrice();
    abstract public function getDuration();
    abstract public function getWorkload();
    abstract public function getCertificate();

}