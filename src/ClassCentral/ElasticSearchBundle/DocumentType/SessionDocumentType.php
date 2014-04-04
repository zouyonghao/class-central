<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/3/14
 * Time: 5:49 PM
 */

namespace ClassCentral\ElasticSearchBundle\DocumentType;


use ClassCentral\ElasticSearchBundle\Types\DocumentType;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Utility\CourseUtility;

/**
 * Class SessionDocumentType
 * Creates a doc for session which are now know as offerings
 * @package ClassCentral\ElasticSearchBundle\DocumentType
 */
class SessionDocumentType extends DocumentType{
    /**
     * Retuns a string that represents the type of
     * @return mixed
     */
    public function getType()
    {
        return 'session';
    }

    /**
     * Returns the id for the document
     * @return mixed
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    public function getBody()
    {

        $o = $this->entity; // The offering
        $b = array(); // The session

        // Offerings that are not valid should not be passed here
        if($o->getStatus() == Offering::COURSE_NA)
        {
            throw new \Exception("The offering is not available any more");
        }

        $b['id'] = $o->getId();
        $b['url'] = $o->getUrl();
        $b['displayDate'] = $o->getDisplayDate();
        $b['startDate'] = $o->getStartDate()->format('d-m-Y');
        $b['startTimeStamp'] = $o->getStartTimestamp();
        $b['microdataDate'] = $o->getMicrodataDate();
        $b['status'] = $o->getStatus();
        $b['courseId'] = $o->getCourse()->getId();
        $b['slug'] = $o->getCourse()->getSlug();

        // get the state
        $b['states'] = CourseUtility::getStates($o);

        return $b;
    }

    /**
     * Retrieves the mapping for a particular type.
     * @return mixed
     */
    public function getMapping()
    {
        // TODO: Implement getMapping() method.
    }

} 