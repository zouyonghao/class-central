<?php

namespace ClassCentral\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Review
 */
class Review
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var float
     */
    private $rating;

    /**
     * @var string
     */
    private $review;

    /**
     * @var integer
     */
    private $hours;

    /**
     * Easy, Medium, Hard, etc.
     * @var integer
     */
    private $difficultyId;

    /**
     * Beginner, Intermediate, Advanced
     * @var integer
     */
    private $levelId;

    /**
     * Completed, Dropped, etc.
     * @var integer
     */
    private $listId;

    /**
     * @var \ClassCentral\SiteBundle\Entity\User
     */
    private $user;

    /**
     * @var \ClassCentral\SiteBundle\Entity\Course
     */
    private $course;

    /**
     * @var \ClassCentral\SiteBundle\Entity\Offering
     */
    private $offering;


    // Difficulty
    const DIFFICULTY_BEGINNER = 1;
    const DIFFICULTY_INTERMEDIATE = 2;
    const DIFFICULTY_ADVANCED = 3;

    public static $difficulty = array(
        self::DIFFICULTY_BEGINNER => array('slug'=>'beginner','desc'=>'Beginner'),
        self::DIFFICULTY_INTERMEDIATE => array('slug'=>'intermediate','desc'=>'Intermediate'),
        self::DIFFICULTY_ADVANCED => array('slug'=>'advanced','desc'=>'Advanced'),
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
     * Set rating
     *
     * @param float $rating
     * @return Review
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
    
        return $this;
    }

    /**
     * Get rating
     *
     * @return float 
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set review
     *
     * @param string $review
     * @return Review
     */
    public function setReview($review)
    {
        $this->review = $review;
    
        return $this;
    }

    /**
     * Get review
     *
     * @return string 
     */
    public function getReview()
    {
        return $this->review;
    }

    /**
     * Set hours
     *
     * @param integer $hours
     * @return Review
     */
    public function setHours($hours)
    {
        $this->hours = $hours;
    
        return $this;
    }

    /**
     * Get hours
     *
     * @return integer 
     */
    public function getHours()
    {
        return $this->hours;
    }

    /**
     * Set difficultyId
     *
     * @param integer $difficultyId
     * @return Review
     */
    public function setDifficultyId($difficultyId)
    {
        $this->difficultyId = $difficultyId;
    
        return $this;
    }

    /**
     * Get difficultyId
     *
     * @return integer 
     */
    public function getDifficultyId()
    {
        return $this->difficultyId;
    }

    /**
     * Set levelId
     *
     * @param integer $levelId
     * @return Review
     */
    public function setLevelId($levelId)
    {
        $this->levelId = $levelId;
    
        return $this;
    }

    /**
     * Get levelId
     *
     * @return integer 
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * Set listId
     *
     * @param integer $listId
     * @return Review
     */
    public function setListId($listId)
    {
        $this->listId = $listId;
    
        return $this;
    }

    /**
     * Get listId
     *
     * @return integer 
     */
    public function getListId()
    {
        return $this->listId;
    }

    /**
     * Set user
     *
     * @param \ClassCentral\SiteBundle\Entity\User $user
     * @return Review
     */
    public function setUser(\ClassCentral\SiteBundle\Entity\User $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \ClassCentral\SiteBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set course
     *
     * @param \ClassCentral\SiteBundle\Entity\Course $course
     * @return Review
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

    /**
     * Set offering
     *
     * @param \ClassCentral\SiteBundle\Entity\Offering $offering
     * @return Review
     */
    public function setOffering(\ClassCentral\SiteBundle\Entity\Offering $offering = null)
    {
        $this->offering = $offering;
    
        return $this;
    }

    /**
     * Get offering
     *
     * @return \ClassCentral\SiteBundle\Entity\Offering 
     */
    public function getOffering()
    {
        return $this->offering;
    }
}
