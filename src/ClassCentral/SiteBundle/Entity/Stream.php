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

    /**
     * @var string $description
     */
    private $description;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \ClassCentral\SiteBundle\Entity\Stream
     */
    private $parentStream;

    /**
     * @var string $imageUrl
     */
    private $imageUrl;

    public function __construct() {
        $this->courses = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $imageUrl
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * Gets the name of the directory on the cdn.
     * i.e subjects/computerscience.jpg
     */
    public function getImageDir()
    {
        return "subjects";
    }


    /**
     * Add courses
     *
     * @param \ClassCentral\SiteBundle\Entity\Course $courses
     * @return Stream
     */
    public function addCourse(\ClassCentral\SiteBundle\Entity\Course $courses)
    {
        $this->courses[] = $courses;
    
        return $this;
    }

    /**
     * Remove courses
     *
     * @param \ClassCentral\SiteBundle\Entity\Course $courses
     */
    public function removeCourse(\ClassCentral\SiteBundle\Entity\Course $courses)
    {
        $this->courses->removeElement($courses);
    }

    /**
     * Add children
     *
     * @param \ClassCentral\SiteBundle\Entity\Stream $children
     * @return Stream
     */
    public function addChildren(\ClassCentral\SiteBundle\Entity\Stream $children)
    {
        $this->children[] = $children;
    
        return $this;
    }

    /**
     * Remove children
     *
     * @param \ClassCentral\SiteBundle\Entity\Stream $children
     */
    public function removeChildren(\ClassCentral\SiteBundle\Entity\Stream $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parentStream
     *
     * @param \ClassCentral\SiteBundle\Entity\Stream $parentStream
     * @return Stream
     */
    public function setParentStream(\ClassCentral\SiteBundle\Entity\Stream $parentStream = null)
    {
        $this->parentStream = $parentStream;
    
        return $this;
    }

    /**
     * Get parentStream
     *
     * @return \ClassCentral\SiteBundle\Entity\Stream 
     */
    public function getParentStream()
    {
        return $this->parentStream;
    }
}