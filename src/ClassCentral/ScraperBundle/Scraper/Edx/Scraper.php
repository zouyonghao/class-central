<?php

namespace ClassCentral\ScraperBundle\Scraper\Edx;

use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Services\Kuber;

class Scraper extends ScraperAbstractInterface
{
    const BASE_URL = "https://www.edx.org";
    const COURSE_CATALOGUE = "https://www.edx.org/course-list/allschools/allsubjects/allcourses";
    const EDX_COURSE_LIST_CSV = "https://www.edx.org/api/report/course-feed/export";
    const EDX_RSS_API = "https://www.edx.org/api/report/course-feed/rss";
    // CONVERTED RSS TO JSON using Yahoo Pipes
    const EDX_RSS_API_JSON = 'http://pipes.yahoo.com/pipes/pipe.run?_id=e2255dca4445cde56275caa98c5f0125&_render=json';

    private $courseFields = array(
        'Url', 'Description', 'Length', 'Name','LongDescription','VideoIntro', 'VerifiedCertificate','Certificate'
    );

    private $offeringFields = array(
        'StartDate', 'EndDate', 'Url'
    );

    /**
     * Using the CSV
     */
    public function scrape()
    {

        $this->buildSelfPacedCourseList();

        $tagService = $this->container->get('tag');

        // Get the course list from the new RSS API
        $edxCourses = file_get_contents(self::EDX_RSS_API_JSON);
        $edxCourses = json_decode( $edxCourses, true);

        foreach( $edxCourses['value']['items'] as $edxCourse )
        {
            $em = $this->getManager();
            $course = $this->getCourseEntity( $edxCourse );

            $cTags = array();
            if(is_array( $edxCourse['course:school'] ))
            {
                foreach( $edxCourse['course:school']  as $school)
                {
                    $cTags[] = strtolower($school);
                }
            }
            else
            {
                $cTags[] = strtolower($edxCourse['course:school'] );
            }


            $dbCourse = $this->dbHelper->getCourseByShortName( $course->getShortName() );

            // Do a fuzzy match on the course title
            if (!$dbCourse)
            {
                $result = $this->findCourseByName( $edxCourse['title'], $this->initiative);
                if( count($result) > 1)
                {
                    $this->out("DUPLICATE ENTRIES FOR: " . $edxCourse['title']);
                    foreach ($result as $item)
                    {
                        $this->out( "COURSE ID" . $item->getId() );
                    }
                    continue;
                }
                else if (count($result) == 1)
                {
                    $dbCourse = $result;
                }
            }

            if( !$dbCourse )
            {

                if($this->doCreate())
                {
                    $this->out("NEW COURSE - " . $course->getName());
                    // NEW COURSE
                    if ($this->doModify())
                    {
                        $em->persist($course);
                        $em->flush();

                        $tagService->saveCourseTags( $course, $cTags);

                        $this->dbHelper->sendNewCourseToSlack( $course, $this->initiative );

                        if($edxCourse['course:image-banner'])
                        {
                            $this->uploadImageIfNecessary( $edxCourse['course:image-banner'], $course);
                        }
                    }
                }
            }
            else
            {
                // Check if any fields are modified
                $courseModified = false;
                $changedFields = array(); // To keep track of fields that have changed
                foreach($this->courseFields as $field)
                {
                    $getter = 'get' . $field;
                    $setter = 'set' . $field;
                    if($course->$getter() != $dbCourse->$getter())
                    {
                        $courseModified = true;

                        // Add the changed field to the changedFields array
                        $changed = array();
                        $changed['field'] = $field;
                        $changed['old'] =$dbCourse->$getter();
                        $changed['new'] = $course->$getter();
                        $changedFields[] = $changed;

                        $dbCourse->$setter($course->$getter());
                    }

                }

                if($courseModified && $this->doUpdate())
                {
                    //$this->out( "Database course changed " . $dbCourse->getName());
                    // Course has been modified
                    $this->out("UPDATE COURSE - " . $dbCourse->getName() . " - ". $dbCourse->getId());
                    $this->outputChangedFields($changedFields);
                    if ($this->doModify())
                    {
                        $em->persist($dbCourse);
                        $em->flush();

                        // Update tags
                        $tagService->saveCourseTags( $dbCourse, $cTags);

                        if($edxCourse['course:image-banner'])
                        {
                            $this->uploadImageIfNecessary( $edxCourse['course:image-banner'], $dbCourse);
                        }
                    }

                }


                $course = $dbCourse;
            }

            /***************************
             * CREATE OR UPDATE OFFERING
             ***************************/
            $offering = new Offering();
            $osn = $this->getOfferingShortName( $edxCourse );
            $offering->setShortName( $osn );
            $offering->setCourse( $course );
            $offering->setUrl( $edxCourse['link'] );
            $offering->setStatus( Offering::START_DATES_KNOWN );
            $offering->setStartDate( new \DateTime( $edxCourse['course:start'] ) );

            if( empty($edxCourse['course:end']) )
            {
                // Put an end date for 4 weeks in the future
                $endDate = new \DateTime(   $edxCourse['course:start'] );
                $endDate->add(new \DateInterval("P30D") );
            }
            else
            {
                $endDate = new \DateTime(  $edxCourse['course:end'] );
            }
            $offering->setEndDate( $endDate );

            $dbOffering = $this->dbHelper->getOfferingByShortName($osn);

            if (!$dbOffering)
            {
                if($this->doCreate())
                {
                    $this->out("NEW OFFERING - " . $offering->getName());
                    if ($this->doModify())
                    {
                        $em->persist($offering);
                        $em->flush();
                    }
                    $this->dbHelper->sendNewOfferingToSlack( $offering);
                    $offerings[] = $offering;
                }
            }
            else
            {
                // old offering. Check if has been modified or not
                $offeringModified = false;
                $changedFields = array();
                foreach ($this->offeringFields as $field)
                {
                    $getter = 'get' . $field;
                    $setter = 'set' . $field;
                    if ($offering->$getter() != $dbOffering->$getter())
                    {
                        $offeringModified = true;
                        // Add the changed field to the changedFields array
                        $changed = array();
                        $changed['field'] = $field;
                        $changed['old'] =$dbOffering->$getter();
                        $changed['new'] = $offering->$getter();
                        $changedFields[] = $changed;
                        $dbOffering->$setter($offering->$getter());
                    }
                }

                if ($offeringModified && $this->doUpdate())
                {
                    // Offering has been modified
                    $this->out("UPDATE OFFERING - " . $dbOffering->getName());
                    $this->outputChangedFields($changedFields);
                    if ($this->doModify())
                    {
                        $em->persist($dbOffering);
                        $em->flush();
                    }
                    $offerings[] = $dbOffering;

                }
            }

        }

        return $offerings;

    }

    private function  getOfferingShortName( $c = array() )
    {
        $edxCourseId = $this->getEdxCourseId( $c['guid'] );
        return 'edx_'. $edxCourseId;
    }

    /**
     * Given an array built from edX csv returns a course entity
     * @param array $c
     */
    private function getCourseEntity ($c = array())
    {
        $defaultStream = $this->dbHelper->getStreamBySlug('cs');
        $langMap = $this->dbHelper->getLanguageMap();
        $defaultLanguage = $langMap[ 'English' ];

        $course = new Course();
        $course->setShortName( $this->getShortName($c) );
        $course->setInitiative( $this->initiative );
        $course->setName( $c['course:code'] . ': ' . $c['title'] );
        $course->setDescription( $c['description'] );
        $course->setLongDescription( nl2br($c['description']) );
        $course->setLanguage( $defaultLanguage);
        $course->setStream($defaultStream); // Default to Computer Science
        $course->setVideoIntro( $c['course:video-youtube']);
        $course->setUrl($c['link']);

        $course->setCertificate( true );
        $course->setVerifiedCertificate( $c['course:verified'] );

        // Calculate length
        $length = null;
        if( !empty($c['course:end']))
        {
            $start = new \DateTime( $c['course:start'] );
            $end = new \DateTime( $c['course:end'] );
            $length = ceil( $start->diff($end)->days/7 );
        }

        $course->setLength( $length );

        return $course;
    }

    private function getShortName( $details )
    {
        $school = $details['course:school'];
        if(is_array($details['course:school']))
        {
            $school = array_pop( $details['course:school'] );
        }
        return 'edx_' . strtolower( $details['course:code'] . '_' . $school );
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
        if (!isset($parsedUrl['query']))
        {
            return null;
        }
        parse_str($parsedUrl['query'], $getParams);
        if(isset($getParams['v']))
        {
            return 'https://www.youtube.com/watch?v=' .  $getParams['v'];
        }

        return null;
    }


    private function parseCourseCode($str)
    {
        $exploded = explode('/',$str);
        return $exploded[3];
    }

    /**
     * Parses the edX from url.
     * i.e /course/wellesley/hist229x/was-alexander-great-life/850 => 850
     * @param $url
     */
    private function getEdxCourseId($url)
    {
        return substr($url, strrpos($url,'/')+1);
    }

    private function getStartDate($html)
    {
        $dateStr = $html->find("div.course-detail-start",0)->plaintext;
        return substr($dateStr,strrpos($dateStr,':')+1);
    }

    /**
     * Used to print the field values which have been modified for both offering and courses
     * @param $changedFields
     */
    private function outputChangedFields($changedFields)
    {
        foreach($changedFields as $changed)
        {
            $field = $changed['field'];
            $old = is_a($changed['old'], 'DateTime') ? $changed['old']->format('jS M, Y') : $changed['old'];
            $new = is_a($changed['new'], 'DateTime') ? $changed['new']->format('jS M, Y') : $changed['new'];

            $this->out("$field changed from - '$old' to '$new'");
        }
    }

    private function uploadImageIfNecessary( $imageUrl, Course $course)
    {
        $kuber = $this->container->get('kuber');
        $uniqueKey = basename($imageUrl);
        if( $kuber->hasFileChanged( Kuber::KUBER_ENTITY_COURSE,Kuber::KUBER_TYPE_COURSE_IMAGE, $course->getId(),$uniqueKey ) )
        {
            // Upload the file
            $filePath = '/tmp/course_'.$uniqueKey;
            file_put_contents($filePath,file_get_contents($imageUrl));
            $kuber->upload(
                $filePath,
                Kuber::KUBER_ENTITY_COURSE,
                Kuber::KUBER_TYPE_COURSE_IMAGE,
                $course->getId(),
                null,
                $uniqueKey
            );

        }
    }

    /**
     * Tries to find a edx course with the particular title
     * @param $title
     * @param $initiative
     */
    private function findCourseByName ($title, Initiative $initiative)
    {
        $em = $this->getManager();
        $result = $em->getRepository('ClassCentralSiteBundle:Course')->createQueryBuilder('c')
                    ->where('c.initiative = :initiative' )
                    ->andWhere('c.name LIKE :title')
                    ->setParameter('initiative', $initiative)
                    ->setParameter('title', '%'.$title)
                    ->getQuery()
                    ->getResult()
        ;

        if ( count($result) == 1)
        {
            return $result[0];
        }

        return null;
    }


    private function buildSelfPacedCourseList()
    {
        $apiUrl = 'https://www.edx.org/search/api/all';
        $selfPacedCourses = array();
        $allCourses = json_decode( file_get_contents($apiUrl), true );
        foreach( $allCourses as $edXCourse)
        {
            $dbCourse = null;
            if ( $edXCourse['pace'] & $edXCourse['availability'] == 'Current' ) // Self paced courses
            {
                $courseShortName = 'edx_' . strtolower( $edXCourse['code'] . '_' .$edXCourse['schools'][0] );

                $dbCourseFromSlug = $this->dbHelper->getCourseByShortName($courseShortName);
                if( $dbCourseFromSlug  )
                {
                    $dbCourse = $dbCourseFromSlug;
                }
                else
                {
                    $dbCourseFromName = $this->findCourseByName( $edXCourse['l'] , $this->initiative );
                    if($dbCourseFromName)
                    {
                        $dbCourse = $dbCourseFromName;
                    }
                }

                if( empty($dbCourse) )
                {
                    $this->out("OnDemand Course Missing : " .  $edXCourse['l']  );
                }
                else
                {
                    $selPaced = false;
                    // Check how many of them are self paced
                    $selfPaced = false;
                    foreach( $dbCourse->getOfferings() as $offering)
                    {
                        if ( $dbCourse->getNextOffering()->getStatus() == Offering::COURSE_OPEN )
                        {
                            $selfPaced = true;
                            break;
                        }
                    }
                    if ( !$selfPaced )
                    {
                        $this->out("OnDemand Session Missing : " . $edXCourse['l'])  ;
                    }
                }

            }
        }
    }

    private function isCourseSelfPaced( $edXCourse )
    {
        if( strpos( $edXCourse['start'], 'Self-paced') !== false )
        {
            return true;
        }
    }

}