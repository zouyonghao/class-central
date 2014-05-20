<?php

namespace ClassCentral\ScraperBundle\Scraper\Edx;

use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Offering;

class Scraper extends ScraperAbstractInterface
{
    const BASE_URL = "https://www.edx.org";
    const COURSE_CATALOGUE = "https://www.edx.org/course-list/allschools/allsubjects/allcourses";
    const EDX_COURSE_LIST_CSV = "/tmp/edx.csv";

    private $courseFields = array(
        'Url', 'Description', 'Length', 'Name','LongDescription','VideoIntro'
    );

    private $offeringFields = array(
        'StartDate', 'EndDate', 'Url'
    );

    /**
     * Using the CSV
     */
    public function scrape()
    {
        $em = $this->getManager();
        $tagService = $this->container->get('tag');

        $csv = file_get_contents(self::EDX_COURSE_LIST_CSV);
        $file = fopen(self::EDX_COURSE_LIST_CSV, 'r');

        fgetcsv($file); // Skip the Header
        while( !feof($file) )
        {
            $c = $this->getEdxArray( fgetcsv($file) );
            $course = $this->getCourseEntity( $c );

            $cTags = array( strtolower($c['school'] ));
            $dbCourse = $this->dbHelper->getCourseByShortName( $course->getShortName() );
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
                    //$this->outputChangedFields($changedFields);
                    if ($this->doModify())
                    {
                        $em->persist($dbCourse);
                        $em->flush();

                        // Update tags
                        $tagService->saveCourseTags( $dbCourse, $cTags);
                    }

                }
                $course = $dbCourse;
            }

            /***************************
             * CREATE OR UPDATE OFFERING
             ***************************/
            $offering = new Offering();
            $osn = $this->getOfferingShortName( $c );
            $offering->setShortName( $osn );
            $offering->setCourse( $course );
            $offering->setUrl( $c['url'] );
            $offering->setStatus( Offering::START_DATES_KNOWN );
            $offering->setStartDate( new \DateTime( $c['startDate'] ) );

            if( empty($c['endDate']) )
            {
                // Put an end date for 4 weeks in the future
                $endDate = new \DateTime(  $c['startDate'] );
                $endDate->add(new \DateInterval("P30D") );
            }
            else
            {
                $endDate = new \DateTime( $c['endDate'] );
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
        $edxCourseId = $this->getEdxCourseId( $c['url'] );
        return 'edx_'. $edxCourseId;
    }


    private function getEdxArray( $line )
    {
        $c = array();
        $c['school'] = $line['4'];
        $c['name'] = $line[5];
        $c['code'] = $line[6];
        $c['startDate'] = $line[8];
        $c['endDate'] = $line[9];
        $c['url'] = $line[10];
        $c['videoIntro'] = $this->getVideoEmbedUrl( $line['11'] );
        $c['description'] = $line[13];

        // Calculate length
        if( !empty($c['endDate']))
        {
            $start = new \DateTime( $line['8'] );
            $end = new \DateTime( $line['9'] );
            $c['length'] = floor( $start->diff($end)->days/7 );
        }
        else
        {
            $c['length'] = null;
        }



        return $c;
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
        $course->setName( $c['code'] . ': ' . $c['name'] );
        $course->setDescription( $c['description'] );
        $course->setLongDescription( $c['description'] );
        $course->setLanguage( $defaultLanguage);
        $course->setStream($defaultStream); // Default to Computer Science
        $course->setVideoIntro( $c['videoIntro']);
        $course->setUrl($c['url']);
        $course->setLength( $c['length'] );

        return $course;
    }

    private function getShortName( $details )
    {
        return 'edx_' . strtolower( $details['code'] . '_' . $details['school'] );
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

    public function scrape1()
    {
        $numberOfPages = $this->getNumberOfPages();

        // Build a list of all course pages
        $coursePageUrls = array();
        for($page = 0; $page <= $numberOfPages; $page++)
        {
            $cataloguePage = file_get_html(self::COURSE_CATALOGUE . "?page=" . $page);
            foreach($cataloguePage->find('div.course-tile') as $courseDiv)
            {
                $coursePageUrls[] = $courseDiv->find('h2.course-title a',0)->href;
            }

        }

        $this->out("Number of courses found - " . count($coursePageUrls));

        $defaultStream = $this->dbHelper->getStreamBySlug('cs');
        foreach( $coursePageUrls as $coursePageUrl)
        {
            $url =  $coursePageUrl;
            $coursePage = file_get_html( $url );

            // Get the course code
            $courseShortName = $this->parseCourseCode($coursePageUrl);
            $courseName =  $coursePage->find('h2.course-detail-title', 0)->plaintext;
            $startDate = $coursePage->find("div.course-detail-start",0)->plaintext;
            $startDate = $this->getStartDate($coursePage);
            $edXCourseId = $this->getEdxCourseId($coursePageUrl);
            $offering = $this->dbHelper->getOfferingByShortName("edx_" . $edXCourseId);
            if(!$offering)
            {
                $this->out("NOT FOUND");
                $this->out("$courseName - $startDate");
                $this->out($url);
                $this->out("");
                continue;
            }

            // Check if the date and url match
            if($offering->getUrl() != $url)
            {
                $this->out("INCORRECT URL");
                $this->out("$courseName - $startDate - Offering Id : {$offering->getId()}");
                $this->out($url);
                $this->out("");
                continue;
            }
            try {
            if($offering->getStatus() == Offering::START_DATES_KNOWN)
            {
                $offeringStartDate = new \DateTime($startDate);
                if($offeringStartDate != $offering->getStartDate() )
                {
                    $this->out("INCORRECT START DATE");
                    $this->out("$courseName - $startDate - Offering Id : {$offering->getId()}");
                    $this->out("Offering Date - {$offering->getDisplayDate()}");
                    $this->out($url);
                    $this->out("");
                }

            }

            if($offering->getStatus() == Offering::START_MONTH_KNOWN && trim($startDate) != $offering->getStartDate()->format("F Y"))
            {
                $this->out("INCORRECT START MONTH");
                $this->out("$courseName - $startDate - Offering Id : {$offering->getId()}");
                $this->out("Offering Date - {$offering->getDisplayDate()}");
                $this->out($url);
                $this->out("");
            }

            } catch(\Exception $e) {
                $this->out("Error parsing dates");
                   $this->out("$courseName - $startDate - Offering Id : {$offering->getId()}");
                   $this->out("Offering Date - {$offering->getDisplayDate()}");
                   $this->out($url);
                   $this->out("");

            }

        }

        return array();

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
        return substr($url, strrpos($url,'-')+1);
    }

    private function getStartDate($html)
    {
        $dateStr = $html->find("div.course-detail-start",0)->plaintext;
        return substr($dateStr,strrpos($dateStr,':')+1);
    }

    /**
     * Calculates the number of pages of course catalogue
     */
    private function getNumberOfPages()
    {
        // Get the first page and then extract the total number of courses
        $this->domParser->load_file(self::COURSE_CATALOGUE);
        $lastPageUrl = $this->domParser->find('li[class="pager-last"] a',0)->href;
        return (int)substr($lastPageUrl,strrpos($lastPageUrl,'=')+1);
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
}