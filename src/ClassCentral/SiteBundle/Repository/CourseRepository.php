<?php

namespace ClassCentral\SiteBundle\Repository;


use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Utility\CourseUtility;
use Doctrine\ORM\EntityRepository;

class CourseRepository extends EntityRepository{

    /**
     * Takes a course entity and builds an array so that
     * it can be serialized and saved in a cache
     * @param Course $course
     */
    public function getCourseArray( Course $course)
    {
        $courseDetails = array();

        $courseDetails['id'] = $course->getId();
        $courseDetails['name'] = $course->getName();
        $courseDetails['videoIntro'] = $course->getVideoIntro();
        $courseDetails['videoEmbedUrl'] = $this->getVideoEmbedUrl($course->getVideoIntro());
        $courseDetails['length'] = $course->getLength();
        $courseDetails['desc'] = $course->getDescription();
        $courseDetails['slug'] = $course->getSlug();
        $courseDetails['url'] = $course->getUrl();
        $courseDetails['nextOffering'] = null;
        $courseDetails['imageUrl'] = CourseUtility::getImageUrl($course);
        $nextOffering = $course->getNextOffering();
        if($nextOffering) {
            $courseDetails['nextOffering']['displayDate'] = $nextOffering->getDisplayDate();
            $courseDetails['nextOffering']['id'] = $nextOffering->getId();
        }
        $courseDetails['tags'] = array();
        foreach( $course->getTags() as $tag)
        {
            $name = $tag->getName();
            if( !empty($name) ) // To account for bug which adds empty tags to courses
            {
                $courseDetails['tags'][] = $name;
            }
        }

        $courseDetails['listed'] = $this->getListedCount($course);
        // Stream
        $stream = $course->getStream();
        $courseDetails['stream']['name'] = $stream->getName();
        $courseDetails['stream']['slug'] = $stream->getSlug();
        $courseDetails['stream']['showInNav'] = $stream->getShowInNav();

        // Initiative
        $initiative = $course->getInitiative();
        $courseDetails['initiative']['name'] = '';
        if ($initiative != null)
        {
            $courseDetails['initiative']['name'] = $initiative->getName();
            $courseDetails['initiative']['url'] = $initiative->getUrl();
            $courseDetails['initiative']['tooltip'] = $initiative->getTooltip();
            $courseDetails['initiative']['code'] = strtolower($initiative->getCode());
        }
        else
        {
            $courseDetails['initiative']['name'] = 'Independent';
            $courseDetails['initiative']['code'] = 'independent';
        }

        // Language
        $lang = array();
        if($course->getLanguage())
        {
            $l = $course->getLanguage();
            $lang['name'] = $l->getName();
            $lang['slug'] = $l->getSlug();
            $lang['code'] = $l->getCode();
        }
        $courseDetails['lang'] = $lang;


        // Institutions
        $courseDetails['institutions'] = array();
        foreach($course->getInstitutions() as $institution)
        {
            $courseDetails['institutions'][] = array(
                'name' => $institution->getName(),
                'url' => $institution->getUrl(),
                'slug' => $institution->getSlug(),
                'isUniversity' => $institution->getIsUniversity(),
            );
        }

        // Instructors
        $courseDetails['instructors'] = array();
        foreach($course->getInstructors() as $instructor)
        {
            $courseDetails['instructors'][] = $instructor->getName();
        }
        $courseDetails['instructorsSingleLineDisplay'] = $this->getInstructorsSingleLineDisplay($courseDetails['instructors']);

        return $courseDetails;
    }

    /**
     * Generates the url to embed video for youtube videos
     * TODO: Should not be here. Move to an appropriate place
     * @param $videoIntro
     * @return null
     */
    private function  getVideoEmbedUrl($videoIntro)
    {
        if(empty($videoIntro))
        {
            return null;
        }

        $parsedUrl = parse_url($videoIntro);
        if (!isset($parsedUrl['query']))
        {
            return null;
        }
        parse_str($parsedUrl['query'], $getParams);
        if(isset($getParams['v']))
        {
            return 'https://www.youtube.com/embed/' .  $getParams['v'] . '?wmode=transparent';
        }

        return null;
    }

    /**
     * Formats the instructors so that it can be displayed in a single line display
     * TODO: Should not be here. Move to an appropriate place
     *
     */
    private function getInstructorsSingleLineDisplay($instructors = array())
    {

        switch(count($instructors))
        {
            case 0:
                return '';
                break;
            case 1:
                return array_pop($instructors);
                break;
            case 2:
                return  implode(' and ',$instructors);
                break;
            default:
                // More than 2 elements
                $last = array_pop($instructors);
                $str = implode($instructors, ', ');

                return $str. ' and ' . $last;
                break;

        }
    }

    /**
     * Retrieves new courses since the given date
     * @param \DateTime $dt
     */
    public function getNewCourses(\DateTime $dt)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query
            ->add('select','c')
            ->add('from','ClassCentralSiteBundle:Course c')
            ->add('where','c.created >= :date')
            ->setParameter('date', $dt->format("Y-m-d"));

        return $query->getQuery()->getResult();
    }

    /**
     * Gets the count of number of times the course has been
     * added to the users list
     */
    public function getListedCount( Course $course)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query
            ->add('select', 'count(uc.id) as listed')
            ->add('from', 'ClassCentralSiteBundle:UserCourse uc')
            ->join('uc.course','c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $course->getId())
            ;

        $listed = $query->getQuery()->getSingleScalarResult();

        return $listed;
    }
}