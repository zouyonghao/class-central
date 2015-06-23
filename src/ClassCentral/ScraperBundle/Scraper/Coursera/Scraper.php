<?php

namespace ClassCentral\ScraperBundle\Scraper\Coursera;

use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\CourseStatus;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Institution;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Services\Kuber;
use ClassCentral\SiteBundle\Utility\PageHeader\PageHeaderFactory;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class Scraper extends ScraperAbstractInterface {

    const COURSES_JSON = 'https://www.coursera.org/maestro/api/topic/list?full=1';
    const INSTRUCTOR_URL = 'https://www.coursera.org/maestro/api/user/instructorprofile?topic_short_name=%s&exclude_topics=1';
    const BASE_URL = 'https://www.coursera.org/course/';
    const COURSE_CATALOG_URL = 'https://api.coursera.org/api/catalog.v1/courses?id=%d&fields=language,aboutTheCourse,courseSyllabus,estimatedClassWorkload&includes=sessions';
    const SESSION_CATALOG_URL = 'https://api.coursera.org/api/catalog.v1/sessions?id=%d&fields=eligibleForCertificates,eligibleForSignatureTrack';
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
        "he" => "Hebrew",
        'pt-br' => 'Portuguese'
    );

    private $courseFields = array(
        'Url', 'SearchDesc', 'Description', 'Length', 'Name', 'Language','LongDescription','Syllabus', 'WorkloadMin', 'WorkloadMax',
        'Certificate', 'VerifiedCertificate', 'VideoIntro'
    );

    private $offeringFields = array(
        'StartDate', 'EndDate', 'Status',
    );

    public function scrape()
    {
        $this->buildOnDemandCoursesList();


        $em = $this->getManager();
        $kuber = $this->container->get('kuber'); // File Api
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

            $catalogDetails = $this->getDetailsFromCourseraCatalog( $courseraCourseId );


            // Create a course object
            $course = new Course();
            $course->setShortName($courseShortName);
            $course->setInitiative($this->initiative);
            $course->setName($courseraCourse['name']);
            $course->setDescription($courseraCourse['short_description']);
            $course->setLongDescription( $catalogDetails['aboutTheCourse']);
            $course->setSyllabus( $catalogDetails['courseSyllabus']);
            $course->setStream($defaultStream); // Default to Computer Science
            $course->setVideoIntro(  $this->getVideoUrl( $courseraCourse  ) );
            $course->setUrl($courseUrl);
            if(isset($dbLanguageMap[$courseLang])) {
                $course->setLanguage($dbLanguageMap[$courseLang]);
            } else {
                $this->out("Language not found " . $courseraCourse['language']);
            }

            // Get the workload
            if( !empty($catalogDetails['estimatedClassWorkload']) && $workload = $this->getWorkLoad($catalogDetails['estimatedClassWorkload']) )
            {
                $course->setWorkloadMin( $workload[0] );
                $course->setWorkloadMax( $workload[1] );
            }

            // Get the certificate information
            $sid = $this->getLatestSessionId( $catalogDetails );
            if( $sid )
            {
                $sDetails  = $this->getDetailsFromSessionCatalog( $sid );
                $course->setCertificate( $sDetails['eligibleForCertificates'] );
                $course->setVerifiedCertificate( $sDetails['eligibleForSignatureTrack'] );
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

            $courseImage =  $courseraCourse['large_icon'];

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

                        $this->dbHelper->sendNewCourseToSlack( $course, $this->initiative );

                        // Upload the image
                        if($courseImage)
                        {
                            $this->uploadImageIfNecessary( $courseImage, $course);
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

                if($this->doUpdate())
                {
                    // Upload the image
                    if($courseImage)
                    {
                        $this->uploadImageIfNecessary( $courseImage, $dbCourse);
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
                        $this->dbHelper->sendNewOfferingToSlack( $offering);
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

    /**
     * Save the html5 video intro and the image in the video intro field
     * @param $courseaCourse
     * @return null|string
     */
    private function getVideoUrl( $courseraCourse )
    {
        if( empty( $courseraCourse['video_baseurl'] ) )
        {
            return null;
        }
        $videoUrl = $courseraCourse['video_baseurl'];
        $image = $courseraCourse['large_icon'];

        return $videoUrl . '|||' . $image;
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
     *
     */
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


    private function buildOnDemandCoursesList( )
    {
        $url = 'https://www.coursera.org/api/courses.v1';
        $onDemandCourses = array();
        $allCourses = json_decode(file_get_contents( $url ),true);
        foreach ($allCourses['elements'] as $element)
        {
            if( $element['courseType'] == 'v2.ondemand')
            {
                $courseShortName = 'coursera_' . $element['slug'];
                $dbCourse = null;
                $dbCourseFromSlug = $this->dbHelper->getCourseByShortName($courseShortName);
                if( $dbCourseFromSlug  )
                {
                    $dbCourse = $dbCourseFromSlug;
                }
                else
                {
                    $dbCourseFromName = $this->findCourseByName( $element['name'], $this->initiative );
                    if($dbCourseFromName)
                    {
                        $dbCourse = $dbCourseFromName;
                    }
                }

                if( empty($dbCourse) )
                {
                    $this->out("OnDemand Course Missing : " . $element['name']);
                }
                else
                {
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
                        $this->out("OnDemand Session Missing : " . $element['name']) ;
                    }
                }

                $onDemandCourses[ $element['slug'] ] = 1;
            }
        }

        return $onDemandCourses;
    }

    private function getDetailsFromCourseraCatalog( $id )
    {
        $url =sprintf(self::COURSE_CATALOG_URL,$id);
        $content = json_decode(file_get_contents( $url ), true);

        return array_pop( $content['elements'] );
    }

    private function getDetailsFromSessionCatalog( $id )
    {
        $url =sprintf(self::SESSION_CATALOG_URL,$id);
        $content = json_decode(file_get_contents( $url ), true);

        return array_pop( $content['elements'] );
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

    /**
     * Parses the coursera workload string into min and max hours
     * @param $workLoad
     */
    private function getWorkLoad( $workload )
    {
        $pos = strpos($workload, 'hours/week');
        if( $pos )
        {
            $workload = substr( $workload, 0, $pos-1);
            return explode( '-', $workload);
        }

        return false;
    }

    private function getLatestSessionId( $catalog )
    {
        if( !empty($catalog['links']['sessions']) )
        {
            return array_pop( $catalog['links']['sessions'] );
        }
    }

    private function findCourseByName ($title, Initiative $initiative)
    {
        $em = $this->getManager();
        $result = $em->getRepository('ClassCentralSiteBundle:Course')->createQueryBuilder('c')
            ->where('c.initiative = :initiative' )
            ->andWhere('c.name LIKE :title')
            ->andWhere('c.status = :status')
            ->setParameter('initiative', $initiative)
            ->setParameter('title', $title)
            ->setParameter('status', CourseStatus::AVAILABLE)
            ->getQuery()
            ->getResult()
        ;
        if ( count($result) == 1)
        {
            return $result[0];
        }

        return null;
    }
}