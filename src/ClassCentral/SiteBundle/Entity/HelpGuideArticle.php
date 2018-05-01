<?php

namespace ClassCentral\SiteBundle\Entity;

/**
 * HelpGuideArticle
 */
class HelpGuideArticle
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $text;

    /**
     * @var string
     */
    private $summary;

    /**
     * @var integer
     */
    private $orderId = '0';

    /**
     * @var string
     */
    private $slug;

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
    private $status;

    /**
     * @var \ClassCentral\SiteBundle\Entity\User
     */
    private $author;

    /**
     * @var \ClassCentral\SiteBundle\Entity\HelpGuideSection
     */
    private $section;


    const HG_ARTICLE_PUBLISHED = 1;
    const HG_ARTICLE_DRAFT = 100;

    public static $statuses = array(
        self::HG_ARTICLE_PUBLISHED => 'Published',
        self::HG_ARTICLE_DRAFT => 'Draft',
    );

    public function __construct()
    {
        $this->created = new \DateTime();
        $this->status = self::HG_ARTICLE_DRAFT;
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
     * Set title
     *
     * @param string $title
     *
     * @return HelpGuideArticle
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
     *
     * @return HelpGuideArticle
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
     * Set summary
     *
     * @param string $summary
     *
     * @return HelpGuideArticle
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get summary
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return HelpGuideArticle
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return HelpGuideArticle
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return HelpGuideArticle
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
     *
     * @return HelpGuideArticle
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
     * Set status
     *
     * @param integer $status
     *
     * @return HelpGuideArticle
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
     * Set author
     *
     * @param \ClassCentral\SiteBundle\Entity\User $author
     *
     * @return HelpGuideArticle
     */
    public function setAuthor(\ClassCentral\SiteBundle\Entity\User $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return \ClassCentral\SiteBundle\Entity\User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set section
     *
     * @param \ClassCentral\SiteBundle\Entity\HelpGuideSection $section
     *
     * @return HelpGuideArticle
     */
    public function setSection(\ClassCentral\SiteBundle\Entity\HelpGuideSection $section = null)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Get section
     *
     * @return \ClassCentral\SiteBundle\Entity\HelpGuideSection
     */
    public function getSection()
    {
        return $this->section;
    }

    public function __toArray()
    {
        return  [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'text' => $this->getText(),
            'summary' => $this->getSummary(),
            'orderId' => $this->getOrderId(),
            'slug' => $this->getSlug(),
            'created' => $this->getCreated()->getTimestamp(),
            'modified' => $this->getModified()->getTimestamp(),
            'status' => $this->getStatus(),
            'section' => $this->getSection()->__toArray(),
            'author' => $this->getAuthor()->__toArray()
        ];
    }
}

