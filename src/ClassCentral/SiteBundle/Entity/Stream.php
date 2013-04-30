<?php

namespace ClassCentral\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClassCentral\SiteBundle\Entity\Stream
 */
class Stream
{
    /**
     * @var integer $id
     */
    private $id;


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
     * @var string $name
     */
    private $name;
    
    private $slug;
    
      /**
     * @var boolean $showInNav
     */
    private $showInNav;
    private $courses = null;

    public function __construct() {
        $this->courses = new \Doctrine\Common\Collections\ArrayCollection();
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
    
    public function __toString() {
        return $this->getName();
    }
    
    public function getCourses(){
        return $this->courses;
    }
    
    public function addCourses($course){
        $this->courses[] = $course;           
    }

    public function getSlug() {
        return $this->slug;
    }
    
    public function setSlug($slug) {
        $this->slug = $slug;
    }
    
    public function getShowInNav(){
        return $this->showInNav;
    }
    
    public function setShowInNav($showInNav){
        $this->showInNav = $showInNav;
    }
}