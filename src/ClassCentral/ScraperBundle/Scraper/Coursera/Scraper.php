<?php

namespace ClassCentral\ScraperBundle\Scraper\Coursera;

use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Institution;
use ClassCentral\SiteBundle\Entity\Offering;

class Scraper extends ScraperAbstractInterface {

    const COURSES_JSON = 'https://www.coursera.org/maestro/api/topic/list?full=1';
    const INSTRUCTOR_URL = 'https://www.coursera.org/maestro/api/user/instructorprofile?topic_short_name=%s&exclude_topics=1';
    const BASE_URL = 'https://www.coursera.org/course/';

    protected static $languageMap = array(
        'en' => "English",
        'en,pt' => "English",
        'fr' => "French",
        "de" => "German",
        "es" => "Spanish",
        "it" => "Italian",
        "zh-Hant" => "Chinese",
        "zh-Hans" => "Chinese",
        "zh-cn" => "Chinese",
        "zh-tw" => "Chinese",
        "ar" => "Arabic",
        "ru" => "Russian",
        "tr" => "Turkish",
        "he" => "Herbrew"
    );

    private $courseFields = array(
        'Url', 'VideoIntro', 'SearchDesc', 'Description', 'Length', 'Name', 'Language'
    );

    private $offeringFields = array(
        'StartDate', 'EndDate', 'Status',
    );

    public function scrape()
    {
        $em = $this->getManager();
        $offerings = array();
        $courseraCourses = $this->getCoursesArray();
        $defaultStream = $this->dbHelper->getStreamBySlug('cs');
        $dbLanguageMap = $this->dbHelper->getLanguageMap();

        foreach($courseraCourses as $courseraCourse)
        {
            $selfServingId = $courseraCourse['self_service_course_id'];
            $courseraCourseId = $courseraCourse['id'];
            $courseraCourseShortName = $courseraCourse['short_name'];
            $courseShortName = 'coursera_' .$courseraCourseShortName;
            $courseUrl = $this->getCourseLink($courseraCourse);
            $courseLang = isset(self::$languageMap[$courseraCourse['language']]) ? self::$languageMap[$courseraCourse['language']] : null ;


            // Create a course object
            $course = new Course();
            $course->setShortName($courseShortName);
            $course->setInitiative($this->initiative);
            $course->setName($courseraCourse['name']);
            $course->setDescription($courseraCourse['short_description']);
            $course->setStream($defaultStream); // Default to Computer Science
            $course->setVideoIntro($this->getYoutubeVideoUrl($courseraCourse['video']));
            $course->setUrl($courseUrl);
            if(isset($dbLanguageMap[$courseLang])) {
                $course->setLanguage($dbLanguageMap[$courseLang]);
            } else {
                $this->out("Language not found " . $courseraCourse['language']);
            }


            // Add the university
            foreach ($courseraCourse['universities'] as $university)
            {
                $ins = new Institution();
                $ins->setName($university['name']);
                $ins->setIsUniversity(true);
                $ins->setSlug($university['short_name']);
                $course->addInstitution($this->dbHelper->createInstitutionIfNotExists($ins));
            }

            // Add categories to search description
            $searchDesc = array();
            foreach ($courseraCourse['categories'] as $category)
            {
                $searchDesc[] = $category['name'];
            }

            $course->setSearchDesc(implode(' ', $searchDesc));

            // Filter out of the offerings to remove those with no status and then get the length of the newest offering
            $courseraOfferings = array_filter($courseraCourse['courses'], function($offering) {
                return !($offering['status'] == 0);
            });
            if(!empty($courseraOfferings))
            {
                $newestOffering = end($courseraOfferings);
                $course->setLength($this->getOfferingLength($newestOffering['duration_string']));
                reset($courseraOfferings);
            }

            $dbCourse = $this->dbHelper->getCourseByShortName($courseShortName);
            if(!$dbCourse)
            {
                if($this->doCreate())
                {
                    // New course
                    $this->out("NEW COURSE - " . $course->getName());
                    if ($this->doModify())
                    {
                       // Get the instructors using the coursera instructor api
                        $courseraInstructors = $this->getInstructorsArray($courseraCourseShortName);
                        foreach ($courseraInstructors as $courseraInstructor)
                        {
                            $insName = $courseraInstructor['first_name'] . ' ' . $courseraInstructor['last_name'];
                            $course->addInstructor($this->dbHelper->createInstructorIfNotExists($insName));
                        }


                        $em->persist($course);
                        $em->flush();
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
                    // Course has been modified
                    $this->out("UPDATE COURSE - " . $dbCourse->getName());
                    $this->outputChangedFields($changedFields);
                    if ($this->doModify())
                    {
                        $em->persist($dbCourse);
                        $em->flush();
                    }

                }

                $course = $dbCourse;
            }

            // Done with course. Now create offerings
            foreach($courseraOfferings as $courseraOffering)
            {
                // Create a offering object and set its parameters
                $offering = new Offering();
                $offeringShortName = $courseraCourseShortName . '_' . $courseraCourseId . '_' . $courseraOffering['id'];
                $offering->setShortName($offeringShortName);
                $offering->setCourse($course);
                $offering->setUrl($courseUrl);

                // Figure out the dates and status
                $details = array();
                $details['status'] = Offering::START_DATES_UNKNOWN;
                if($selfServingId == $courseraOffering['id'])
                {
                    $details['status'] = Offering::COURSE_OPEN;
                }
                $details =  array_merge($details,$this->getDates($courseraOffering, $this->getOfferingLength($courseraOffering['duration_string'])));

                $offering->setStartDate(new \DateTime($details['start_date']));
                $offering->setStatus($details['status']);
                if(isset($details['end_date']))
                {
                    $offering->setEndDate(new \DateTime($details['end_date']));
                }


                $dbOffering = $this->dbHelper->getOfferingByShortName($offeringShortName);
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
                } else
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
        }

        return $offerings;
    }

    private function getCoursesArray()
    {
        $this->out("Getting the coursera json");
        return json_decode(file_get_contents(self::COURSES_JSON), true);
    }

    private function getInstructorsArray($shortName)
    {
        return json_decode(
            file_get_contents(sprintf(self::INSTRUCTOR_URL, $shortName)),
            true
        );
    }

    private function getYoutubeVideoUrl($video = '')
    {
        return (strlen($video) > 1)? 'https://www.youtube.com/watch?v='. $video : null;
    }

    private function getCourseLink( $course ){
        if(!empty($course['social_link'])){
            return $course['social_link'];
        }

        return self::BASE_URL. $course['short_name'];
    }

    // Duration is of the format '6 weeks'
    private function  getOfferingLength($duration)
    {
        if( $duration == '' )
        {
            return null;
        }

        if( $duration == '6-8 weeks' )
        {
            return 8;
        }

        if( $duration == '4 - 5 weeks' )
        {
            return 5;
        }

        $parts = explode(' ', $duration);
        return $parts[0];
    }

    private function getDates ($offering, $length )
    {
        $save = array();
        $start_date = $offering['start_date_string'];
        $day = $offering['start_day'];
        $month = $offering['start_month'];
        $year = $offering['start_year'];

        // Ignoring start date string
        if(!$year){
            $save['start_date'] = '2015-12-31';
        }
        else{
            // Format month into 01,02 etc
            if($month && $month < 10){ $month = '0'.$month;}
            if($day && $day < 10){ $day = '0'.$day;}

            if($month && $day)
            {
                $save['status'] = Offering::START_DATES_KNOWN; // Start dates known
                $save['start_date'] = $year . '-'. $month . '-' . $day;
            } else if($month){
                $save['status'] = Offering::START_MONTH_KNOWN; // Start Month Known
                $save['start_date'] = $year . '-'. $month . '-' . 28;
            } else {
                $save['status'] = Offering::START_YEAR_KNOWN;// Start year known
                $save['start_date'] = $year . '-' . '12-31';
            }
            if ( $length )
            {
                $days = $length * 7;
                $start_date_obj = new \DateTime( $save['start_date']);
                // Calculate end date
                $save['end_date'] = $start_date_obj->add(new \DateInterval("P{$days}D"))->format("Y-m-d");
            }
        }

        return $save;
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