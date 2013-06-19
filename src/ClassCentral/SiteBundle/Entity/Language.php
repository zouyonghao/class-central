<?php

namespace ClassCentral\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClassCentral\SiteBundle\Entity\Language
 */
class Language
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var ClassCentral\SiteBundle\Entity\Course
     */
    private $courses;

    public function __construct()
    {
        $this->courses = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add courses
     *
     * @param ClassCentral\SiteBundle\Entity\Course $courses
     */
    public function addCourse(\ClassCentral\SiteBundle\Entity\Course $courses)
    {
        $this->courses[] = $courses;
    }

    /**
     * Get course
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getCourses()
    {
        return $this->courses;
    }

    public function __toString() {
        return $this->getName();
    }

}