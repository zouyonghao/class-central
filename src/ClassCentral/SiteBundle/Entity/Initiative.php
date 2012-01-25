<?php

namespace ClassCentral\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClassCentral\SiteBundle\Entity\Initiative
 */
class Initiative
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
     * @var string $url
     */
    private $url;

    /**
     * @var string $description
     */
    private $description;

    /**
     * @var string $code
     */
    private $code;

    /**
     * @var ClassCentral\SiteBundle\Entity\Offering
     */
    private $offerings;

    public function __construct()
    {
        $this->offerings = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set url
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set description
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set code
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Add offerings
     *
     * @param ClassCentral\SiteBundle\Entity\Offering $offerings
     */
    public function addOffering(\ClassCentral\SiteBundle\Entity\Offering $offerings)
    {
        $this->offerings[] = $offerings;
    }

    /**
     * Get offerings
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getOfferings()
    {
        return $this->offerings;
    }
}