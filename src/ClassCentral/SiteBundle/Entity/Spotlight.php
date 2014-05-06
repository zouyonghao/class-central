<?php

namespace ClassCentral\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Spotlight
 */
class Spotlight
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $position;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $imageUrl;

    const SPOTLIGHT_TYPE_DEMO = 1; // Only show in dev
    const SPOTLIGHT_TYPE_COURSE = 2;
    const SPOTLIGHT_TYPE_NEWS = 3;
    const SPOTLIGHT_TYPE_INTERVIEW = 4;
    const SPOTLIGHT_TYPE_AD = 5;

    public static $spotlightMap = array(
        self::SPOTLIGHT_TYPE_DEMO => array( 'class' => 'spotlight-article','text' => 'View Demo'),
        self::SPOTLIGHT_TYPE_COURSE => array( 'class' => 'spotlight-course','text' => 'View Course'),
        self::SPOTLIGHT_TYPE_NEWS => array( 'class' => 'spotlight-article','text' => 'Read Article'),
        self::SPOTLIGHT_TYPE_INTERVIEW => array( 'class' => 'spotlight-interview','text' => 'Read Interview'),
        self::SPOTLIGHT_TYPE_AD => array( 'class' => 'spotlight-sponsor','text' => 'View Sponsor'),
    );


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
     * Set position
     *
     * @param integer $position
     * @return Spotlight
     */
    public function setPosition($position)
    {
        $this->position = $position;
    
        return $this;
    }

    /**
     * Get position
     *
     * @return integer 
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Spotlight
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Spotlight
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
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
     * Set url
     *
     * @param string $url
     * @return Spotlight
     */
    public function setUrl($url)
    {
        $this->url = $url;
    
        return $this;
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
     * Set imageUrl
     *
     * @param string $imageUrl
     * @return Spotlight
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
    
        return $this;
    }

    /**
     * Get imageUrl
     *
     * @return string 
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }
    /**
     * @var integer
     */
    private $type;


    /**
     * Set type
     *
     * @param integer $type
     * @return Spotlight
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }
}