<?php

namespace ClassCentral\CredentialBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CredentialReview
 */
class CredentialReview
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
    private $title;

    /**
     * @var string
     */
    private $text;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var \DateTime
     */
    private $dateCompleted;

    /**
     * @var string
     */
    private $link;

    /**
     * @var float
     */
    private $topicCoverage;

    /**
     * @var float
     */
    private $jobReadiness;

    /**
     * @var float
     */
    private $communitySupport;

    /**
     * @var integer
     */
    private $effort;

    /**
     * @var integer
     */
    private $duration;

    /**
     * @var string
     */
    private $reviewerName;

    /**
     * @var string
     */
    private $reviewerEmail;

    /**
     * @var string
     */
    private $reviewerJobTitle;

    /**
     * @var string
     */
    private $reviewerHighestDegree;

    /**
     * @var string
     */
    private $reviewerFieldOfStudy;

    /**
     * @var \ClassCentral\CredentialBundle\Entity\Credential
     */
    private $credential;

    /**
     * @var \ClassCentral\SiteBundle\Entity\User
     */
    private $user;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $modified;

    /**
     * @var integer
     */
    private $progress;


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
     * @return CredentialReview
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
     * Set title
     *
     * @param string $title
     * @return CredentialReview
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
     * Set text
     *
     * @param string $text
     * @return CredentialReview
     */
    public function setText($text)
    {
        $this->text = $text;
    
        return $this;
    }

    /**
     * Get text
     *
     * @return string 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return CredentialReview
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set dateCompleted
     *
     * @param \DateTime $dateCompleted
     * @return CredentialReview
     */
    public function setDateCompleted($dateCompleted)
    {
        $this->dateCompleted = $dateCompleted;
    
        return $this;
    }

    /**
     * Get dateCompleted
     *
     * @return \DateTime 
     */
    public function getDateCompleted()
    {
        return $this->dateCompleted;
    }

    /**
     * Set link
     *
     * @param string $link
     * @return CredentialReview
     */
    public function setLink($link)
    {
        $this->link = $link;
    
        return $this;
    }

    /**
     * Get link
     *
     * @return string 
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set topicCoverage
     *
     * @param float $topicCoverage
     * @return CredentialReview
     */
    public function setTopicCoverage($topicCoverage)
    {
        $this->topicCoverage = $topicCoverage;
    
        return $this;
    }

    /**
     * Get topicCoverage
     *
     * @return float 
     */
    public function getTopicCoverage()
    {
        return $this->topicCoverage;
    }

    /**
     * Set jobReadiness
     *
     * @param float $jobReadiness
     * @return CredentialReview
     */
    public function setJobReadiness($jobReadiness)
    {
        $this->jobReadiness = $jobReadiness;
    
        return $this;
    }

    /**
     * Get jobReadiness
     *
     * @return float 
     */
    public function getJobReadiness()
    {
        return $this->jobReadiness;
    }

    /**
     * Set communitySupport
     *
     * @param float $communitySupport
     * @return CredentialReview
     */
    public function setCommunitySupport($communitySupport)
    {
        $this->communitySupport = $communitySupport;
    
        return $this;
    }

    /**
     * Get communitySupport
     *
     * @return float 
     */
    public function getCommunitySupport()
    {
        return $this->communitySupport;
    }

    /**
     * Set effort
     *
     * @param integer $effort
     * @return CredentialReview
     */
    public function setEffort($effort)
    {
        $this->effort = $effort;
    
        return $this;
    }

    /**
     * Get effort
     *
     * @return integer 
     */
    public function getEffort()
    {
        return $this->effort;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     * @return CredentialReview
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    
        return $this;
    }

    /**
     * Get duration
     *
     * @return integer 
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set reviewerName
     *
     * @param string $reviewerName
     * @return CredentialReview
     */
    public function setReviewerName($reviewerName)
    {
        $this->reviewerName = $reviewerName;
    
        return $this;
    }

    /**
     * Get reviewerName
     *
     * @return string 
     */
    public function getReviewerName()
    {
        return $this->reviewerName;
    }

    /**
     * Set reviewerEmail
     *
     * @param string $reviewerEmail
     * @return CredentialReview
     */
    public function setReviewerEmail($reviewerEmail)
    {
        $this->reviewerEmail = $reviewerEmail;
    
        return $this;
    }

    /**
     * Get reviewerEmail
     *
     * @return string 
     */
    public function getReviewerEmail()
    {
        return $this->reviewerEmail;
    }

    /**
     * Set reviewerJobTitle
     *
     * @param string $reviewerJobTitle
     * @return CredentialReview
     */
    public function setReviewerJobTitle($reviewerJobTitle)
    {
        $this->reviewerJobTitle = $reviewerJobTitle;
    
        return $this;
    }

    /**
     * Get reviewerJobTitle
     *
     * @return string 
     */
    public function getReviewerJobTitle()
    {
        return $this->reviewerJobTitle;
    }

    /**
     * Set reviewerHighestDegree
     *
     * @param string $reviewerHighestDegree
     * @return CredentialReview
     */
    public function setReviewerHighestDegree($reviewerHighestDegree)
    {
        $this->reviewerHighestDegree = $reviewerHighestDegree;
    
        return $this;
    }

    /**
     * Get reviewerHighestDegree
     *
     * @return string 
     */
    public function getReviewerHighestDegree()
    {
        return $this->reviewerHighestDegree;
    }

    /**
     * Set reviewerFieldOfStudy
     *
     * @param string $reviewerFieldOfStudy
     * @return CredentialReview
     */
    public function setReviewerFieldOfStudy($reviewerFieldOfStudy)
    {
        $this->reviewerFieldOfStudy = $reviewerFieldOfStudy;
    
        return $this;
    }

    /**
     * Get reviewerFieldOfStudy
     *
     * @return string 
     */
    public function getReviewerFieldOfStudy()
    {
        return $this->reviewerFieldOfStudy;
    }

    /**
     * Set credential
     *
     * @param \ClassCentral\CredentialBundle\Entity\Credential $credential
     * @return CredentialReview
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
     * Set user
     *
     * @param \ClassCentral\SiteBundle\Entity\User $user
     * @return CredentialReview
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
     * Set created
     *
     * @param \DateTime $created
     * @return Credential
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set modified
     *
     * @param \DateTime $modified
     * @return Credential
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified
     *
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }


    /**
     * Set progress
     *
     * @param integer $progress
     * @return CredentialReview
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;
    
        return $this;
    }

    /**
     * Get progress
     *
     * @return integer 
     */
    public function getProgress()
    {
        return $this->progress;
    }
}