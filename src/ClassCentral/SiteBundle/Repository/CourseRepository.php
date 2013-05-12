<?php

namespace ClassCentral\SiteBundle\Repository;


use ClassCentral\SiteBundle\Entity\Course;
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
        }

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
        return $courseDetails;
    }

    /**
     * Generates the url to embed video for youtube videos
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
        parse_str($parsedUrl['query'], $getParams);
        if($getParams['v'])
        {
            return 'http://www.youtube.com/embed/' .  $getParams['v'];
        }

        return null;
    }
}