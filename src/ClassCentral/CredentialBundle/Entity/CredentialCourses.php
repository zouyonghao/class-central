<?php

namespace ClassCentral\CredentialBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CredentialCourses
 */
class CredentialCourses
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $order;

    /**
     * @var \ClassCentral\CredentialBundle\Entity\Credential
     */
    private $credential;

    /**
     * @var \ClassCentral\SiteBundle\Entity\Course
     */
    private $course;


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
     * Set order
     *
     * @param integer $order
     * @return CredentialCourses
     */
    public function setOrder($order)
    {
        $this->order = $order;
    
        return $this;
    }

    /**
     * Get order
     *
     * @return integer 
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set credential
     *
     * @param \ClassCentral\CredentialBundle\Entity\Credential $credential
     * @return CredentialCourses
     */
    public function setCredential(\ClassCentral\CredentialBundle\Entity\Credential $credential = null)
    {
        $this->credential = $credential;
    
        return $this;
    }

    /**
     * Get credential
     *
     * @return \ClassCentral\CredentialBundle\Entity\Credential 
     */
    public function getCredential()
    {
        return $this->credential;
    }

    /**
     * Set course
     *
     * @param \ClassCentral\SiteBundle\Entity\Course $course
     * @return CredentialCourses
     */
    public function setCourse(\ClassCentral\SiteBundle\Entity\Course $course = null)
    {
        $this->course = $course;
    
        return $this;
    }

    /**
     * Get course
     *
     * @return \ClassCentral\SiteBundle\Entity\Course 
     */
    public function getCourse()
    {
        return $this->course;
    }
}
