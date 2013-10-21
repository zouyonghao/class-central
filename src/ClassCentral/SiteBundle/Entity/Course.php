<?php

namespace ClassCentral\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ClassCentral\SiteBundle\Entity\Course
 */
class Course {

    public function __construct() {
        $this->offerings = new ArrayCollection();   
        $this->institutions = new ArrayCollection();
        $this->instructors = new ArrayCollection();
        $this->setCreated(new \DateTime());
    }

    /**
     * @var integer $id
     */
    private $id;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var date $start_date
     */
    private $start_date;

    /**
     * @var boolean $exact_date_known
     */
    private $exact_date_known;

    /**
     * @var integer $stream_id
     */
   // private $stream_id;

    /**
     * @var ClassCentral\SiteBundle\Entity\Stream
     */
    private $stream;
    protected $offerings;    
    
     /**
     * @var ClassCentral\SiteBundle\Entity\Initiative
     */
    private $initiative;

    /**
     * @var ClassCentral\SiteBundle\Entity\Language
     */
    private $language;
    
    /**
     * @var ClassCentral\SiteBundle\Entity\Institution
     */
    private $institutions;

    /**
     *
     * @var integer length
     */
    private $length;

    private $searchDesc;

    /**
     *
     * @var status
     */
    private $status;

    /**
     * @var ClassCentral\SiteBundle\Entity\Instructor
     */
    private $instructors;

    private $shortName;

    private $description;

    /**
     * @var datetime $created
     */
    private $created;

    /**
     * @var datetime $modified
     */
    private $modified;

    /**
     *
     * @var string $url
     */
    private $url;

    /*
     * Generated url for the course page
     */
    private $slug;

    /**
     *
     * @var string $videoIntro
     */
    private $videoIntro;

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set start_date
     *
     * @param date $startDate
     */
    public function setStartDate($startDate) {
        $this->start_date = $startDate;
    }

    /**
     * Get start_date
     *
     * @return date 
     */
    public function getStartDate() {
        return $this->start_date;
    }

    /**
     * Set exact_date_known
     *
     * @param boolean $exactDateKnown
     */
    public function setExactDateKnown($exactDateKnown) {
        $this->exact_date_known = $exactDateKnown;
    }

    /**
     * Get exact_date_known
     *
     * @return boolean 
     */
    public function getExactDateKnown() {
        return $this->exact_date_known;
    }

    /**
     * Set stream
     *
     * @param ClassCentral\SiteBundle\Entity\Stream $stream
     */
    public function setStream(\ClassCentral\SiteBundle\Entity\Stream $stream) {
        $this->stream = $stream;
    }

    /**
     * Get stream
     *
     * @return ClassCentral\SiteBundle\Entity\Stream 
     */
    public function getStream() {
        return $this->stream;
    }
   
    public function getOfferings() {
        return $this->offerings;
    }
    
    
    /**
     * Set initiative
     * 
     * @param ClassCEntral\SiteBundle\Entitiy\Offering $offering
     */
    public function setInitiative(\ClassCentral\SiteBundle\Entity\Initiative $initiative = null) {
        $this->initiative = $initiative;
    }

    /**
     * Get Initative
     * 
     * @return ClassCentral\SiteBundle\Entity\Initiative
     */
    public function getInitiative() {
        return $this->initiative;
    }

    /**
     * @param Language $lang
     */
    public function setLanguage(\ClassCentral\SiteBundle\Entity\Language $lang) {
        $this->language = $lang;
    }

    /**
     * @return ClassCentral\SiteBundle\Entity\Language
     */
    public  function getLanguage() {
        return $this->language;
    }
    
     /**
     * Add institution
     *
     * @param ClassCentral\SiteBundle\Entity\Institution $institutions
     */
    public function addInstitution(\ClassCentral\SiteBundle\Entity\Institution $institutions)
    {
        $this->institutions[] = $institutions;
    }

    /**
     * Get institutions
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getInstitutions()
    {
        return $this->institutions;
    }

    /**
     * Set created
     *
     * @param datetime $created
     */
    public function setCreated($created) {
        $this->created = $created;
    }

    /**
     * Get created
     *
     * @return datetime
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * Set modified
     *
     * @param datetime $modified
     */
    public function setModified($modified) {
        $this->modified = $modified;
    }

    /**
     * Get modified
     *
     * @return datetime
     */
    public function getModified() {
        return $this->modified;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function getVideoIntro() {
        return $this->videoIntro;
    }

    public function setVideoIntro($videoIntro) {
        $this->videoIntro = $videoIntro;
    }

    public function getLength() {
        return $this->length;
    }

    public function setLength($length) {
        $this->length = $length;
    }

    public function getInstructors() {
        return $this->instructors;
    }

    public function addInstructor(Instructor $instructor) {
        $this->instructors[] = $instructor;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($desc) {
        $this->description = $desc;
    }

    public function setShortName($shortName) {
        $this->shortName = $shortName;
    }

    public function getShortName() {
        return $this->shortName;
    }

    public function getSearchDesc() {
        return $this->searchDesc;
    }
    public function setSearchDesc($desc) {
        $this->searchDesc = $desc;
    }

    /**
     * http://stackoverflow.com/questions/7568231/php-remove-url-not-allowed-characters-from-string
     * @return mixed
     */
    public function getSlug(){
        $initiative = '';
        if($this->getInitiative() != null ) {
            $initiative = $this->getInitiative()->getName();
        }
        $url = preg_replace('~[^\\pL0-9_]+~u', '-', $initiative . ' ' . $this->getName());
        $url = trim($url, "-");
        $url = iconv("utf-8", "us-ascii//TRANSLIT", $url);
        $url = strtolower($url);
        $url = preg_replace('~[^-a-z0-9_]+~', '', $url);

        return $url;
    }

    /**
     * Get the next offering for this course
     */
    public function getNextOffering()
    {
        /**
         * Filter out offerings which are not available
         */

        $this->getOfferings()->filter(
            function($offering)
            {
                return $offering->getStatus() == Offering::COURSE_NA;
            }
        );

        $offerings = $this->getOfferings();
        if(empty($offerings))
        {
            //TODO: Handle it correctly
            return;
        }

        $nextOffering = $offerings->first();
        foreach($offerings as $offering)
        {
            if($offering->getStartDate() > $nextOffering->getStartDate())
            {
                $nextOffering = $offering;
            }
        }

        return $nextOffering;
    }

    public function __toString() {
        return $this->getName();
    }

}