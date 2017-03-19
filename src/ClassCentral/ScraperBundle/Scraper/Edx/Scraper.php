<?php

namespace ClassCentral\ScraperBundle\Scraper\Edx;

use ClassCentral\CredentialBundle\Entity\Credential;
use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Services\Kuber;
use ClassCentral\SiteBundle\Utility\UniversalHelper;
use GuzzleHttp\Client;

class Scraper extends ScraperAbstractInterface
{
    const BASE_URL = "https://www.edx.org";
    const COURSE_CATALOGUE = "https://www.edx.org/course-list/allschools/allsubjects/allcourses";
    const EDX_COURSE_LIST_CSV = "https://www.edx.org/api/report/course-feed/export";
    const EDX_RSS_API = "https://www.edx.org/api/v2/report/course-feed/rss?page=%s";
    const EDX_CARDS_API = "https://www.edx.org/api/discovery/v1/course_run_cards";
    const EDX_ENROLLMENT_COURSE_DETAIL = 'https://courses.edx.org/api/enrollment/v1/course/%s?include_expired=1'; // Contains pricing information
    const EDX_API_ALL_COURSES_BASE_v1 = 'https://api.edx.org';
    const EDX_API_ALL_COURSES_PATH_v1 = '/catalog/v1/catalogs/11/courses/';

    const EDX_DRUPAL_CATALOG = 'https://www.edx.org/api/v1/catalog/search?page_size=2000&partner=edx&content_type[]=courserun';
    const EDX_DRUPAL_INDIVIDUAL = 'https://www.edx.org/api/catalog/v2/courses/%s';
    const EDX_DRUPAL_COURSE_RUNS =  'https://www.edx.org/api/v1/catalog/course_runs/%s'; // contains uuids required for sessions/offerings
    const EDX_DRUPAL_COURSE_MODES = 'https://courses.edx.org/api/enrollment/v1/course/%s'; // contains pricing ino

    public STATIC $EDX_XSERIES_GUID = array(15096, 7046, 14906,14706,7191, 13721,13296, 14951, 13251,15861, 15381
        ,15701, 7056
    );
    private $credentialFields = array(
        'Url','Description','Name', 'OneLiner', 'SubTitle'
    );


    private $courseFields = array(
        'Url', 'Description', 'Name','LongDescription','VideoIntro','Certificate',
        'CertificatePrice','ShortName','Syllabus','IsMooc'
    );

    private $offeringFields = array(
        'StartDate', 'EndDate', 'Url','ShortName','Status'
    );

    private $subjectsMap = array(
        'Computer Science' => 'cs',
        'Data Analysis & Statistics' => 'data-science',
        'Biology & Life Sciences' => 'biology',
        'Education & Teacher Training' => 'education',
        'Engineering' => 'engineering',
        'Economics & Finance' => 'economics',
        'Science' => 'science',
        'Social Sciences' => 'social-sciences',
        'Physics' => 'Physics',
        'Business & Management' => 'business',
        'Humanities' => 'humanities',
        'Law' => 'law',
        'History' => 'history',
        'Communication' => 'communication-skills',
        'Literature' => 'literature',
        'Math' => 'maths',
        'Food & Nutrition' =>'nutrition-and-wellness',
        'Art & Culture' => 'art-and-design',
        'Chemistry' => 'chemistry',
        'Health & Safety' => 'health',
        'Philosophy & Ethics' => 'philosophy',
        'Language' => 'language-culture',
        'Music' => 'music',
        'Electronics' => 'electrical-engineering',
        'Design' => 'art-and-design',
        'Environmental Studies' => 'environmental-science',
        'Medicine' => 'health',
        'Architecture' => 'visual-arts',
        'Energy & Earth Sciences' => 'environmental-science',
        'Ethics' => 'social-sciences',
    );


    private $skipNames = array('DELETE','OBSOLETE','STAGE COURSE', 'Test Course');

    private $coursesWithSameName = array('Introduction to Differential Equations');

    /**
     * Using the CSV
     */
    public function scrape()
    {

        if($this->isCredential)
        {
            $this->scrapeCredentials();
            return;
        }

        $tagService = $this->container->get('tag');
        $em = $this->getManager();

        /**
         * The edX.org drupal API used to render courses pages and course catalog
         */
        // Build the catalog.
        $edxCourses = $this->getEdxDrupalJson();
        $duplicateOfferings = 0;
        foreach($edxCourses as $edxCourse)
        {
            $course = $this->getCourseEntityFromDrupalAPI($edxCourse);

            $cTags = array();
            foreach( $edxCourse['course_page_info']['schools']  as $school)
            {
                $cTags[] = strtolower($school['title']);
            }

            $dbCourse = $this->dbHelper->getCourseByShortName( $course->getShortName() );

            // Do a fuzzy match on the course title. Don't match for courses with same names
            if (!$dbCourse && !in_array($course->getName(),$this->coursesWithSameName))
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
                        if(!empty($edxCourse['course_page_info']['staff']))
                        {
                            foreach( $edxCourse['course_page_info']['staff'] as $staff )
                            {
                                $insName = $staff['title'];
                                if(!empty($insName))
                                {
                                    $course->addInstructor($this->dbHelper->createInstructorIfNotExists($insName));
                                }
                            }
                        }


                        $em->persist($course);
                        $em->flush();

                        $tagService->saveCourseTags( $course, $cTags);

                        $this->dbHelper->sendNewCourseToSlack( $course, $this->initiative );

                        if( $edxCourse['course_page_info']['image'] )
                        {
                            $this->uploadImageIfNecessary( $edxCourse['course_page_info']['image'], $course);
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

                        if( $edxCourse['course_page_info']['image'] )
                        {
                            $this->uploadImageIfNecessary( $edxCourse['course_page_info']['image'], $dbCourse);
                        }
                    }

                }

                $course = $dbCourse;
            }

            /***************************
             * CREATE OR UPDATE OFFERING
             ***************************/

            // Check if the course run is not empty
            if(empty($edxCourse['course_runs']))
            {
                continue;
            }

            $offering = $this->getOffering($edxCourse,$course);

            $dbOffering = $this->dbHelper->getOfferingByShortName($offering->getShortName());

            // figure out if its a duplicate
            if(!$dbOffering)
            {
                foreach($course->getOfferings() as $off)
                {
                    if( $off->getStartDate()->format('Y-m') == $offering->getStartDate()->format('Y-m') )
                    {
                        $dbOffering = $off;
                        $duplicateOfferings++;
                        break;
                    }
                }
            }

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

        $this->out(  $duplicateOfferings );

        exit();


        /**
         * NEW OFFICIAL API
         */
        $accessToken = $this->getAccessToken();
        $client = new Client([
            'base_uri' => self::EDX_API_ALL_COURSES_BASE_v1,
        ]);
        $em = $this->getManager();
        $nextUrl = self::EDX_API_ALL_COURSES_PATH_v1;
        while($nextUrl) {

            $edxCourses = json_decode( $this->getedXJson($client,$nextUrl),true);
            //$this->out(json_encode($edxCourses));

            foreach($edxCourses['results'] as $edxCourse)
            {
                $course = $this->getCourseEntity($edxCourse);

                // Check if this course has to be skipped
                $skip = false;
                foreach($this->skipNames as $skipName)
                {
                    if (strpos(strtolower($course->getName()), strtolower($skipName)) !== false) {
                        $skip = true;
                        $this->out("SKIPPING  " . $course->getName());
                        break;
                    }
                }
                if($skip) continue;

                // Get the latest run of the course
                $latestRun = null;
                if(!empty($edxCourse['course_runs']))
                {
                    $latestRun = array_pop($edxCourse['course_runs']);
                }

                $cTags = array();
                foreach( $edxCourse['owners']  as $school)
                {
                    $cTags[] = strtolower($school['key']);
                }

                $dbCourse = $this->dbHelper->getCourseByShortName( $course->getShortName() );

                // Use the old shortname to pull the title
                if(!$dbCourse)
                {
                    $dbCourse = $this->dbHelper->getCourseByShortName( $this->getOldShortName($edxCourse['key']));
                }

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


                if(!$dbCourse)
                {
                    $this->out($course->getName() . ' - ' . $edxCourse['uuid']);
                }
                continue;

                if( !$dbCourse )
                {

                    if($this->doCreate())
                    {
                        $this->out("NEW COURSE - " . $course->getName());
                        // NEW COURSE
                        if ($this->doModify())
                        {

                            if(!empty($latestRun['staff']))
                            {
                                foreach( $latestRun['staff'] as $staff )
                                {
                                    $insName = $staff['name'];
                                    if(!empty($insName))
                                    {
                                        $course->addInstructor($this->dbHelper->createInstructorIfNotExists($insName));
                                    }
                                }
                            }


                            $em->persist($course);
                            $em->flush();

                            $tagService->saveCourseTags( $course, $cTags);

                            $this->dbHelper->sendNewCourseToSlack( $course, $this->initiative );

                            if($latestRun['image']['src'])
                            {
                                $this->uploadImageIfNecessary( $latestRun['image']['src'], $course);
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

                            if($latestRun['image']['src'])
                            {
                                $this->uploadImageIfNecessary($latestRun['image']['src'], $dbCourse);
                            }
                        }

                    }


                    $course = $dbCourse;
                }

                foreach($edxCourse['course_runs'] as $run)
                {
                    $offering = $this->getOfferingFromCourseRun($run,$course);
                }


            }

            $nextUrl = $edxCourses['next'];
            $this->out( $nextUrl );
        }

        echo $edxCourses['count'] . "\n";
        echo $edxCourses['next'] . "\n";

        exit();
        while(true) {

            $page++;
            $this->out("Retrieving PAGE #" . $page);
            $edxCourses = file_get_contents(sprintf(self::EDX_RSS_API,$page));
            $edxCourses = str_replace("course:","course-",$edxCourses);
            $edxCourses = str_replace("staff:","staff-",$edxCourses);
            $simpleXml = simplexml_load_string($edxCourses);
            $edxCourses = json_encode($simpleXml);

            $edxCourses = json_decode( $edxCourses, true);
            if(empty( $edxCourses['channel']['item']))
            {
                break;
            }

        foreach( $edxCourses['channel']['item'] as $edxCourse )
        {
            $em = $this->getManager();
            $course = $this->getCourseEntity( $edxCourse );
            $cTags = array();
            if(is_array( $edxCourse['course-school'] ))
            {
                foreach( $edxCourse['course-school']  as $school)
                {
                    $cTags[] = strtolower($school);
                }
            }
            else
            {
                $cTags[] = strtolower($edxCourse['course-school'] );
            }

            $courseId = $edxCourse['course-id'];

            /**
            $fileName = "/tmp/edx/{$edxCourse['title']}.json";
            if( file_exists($fileName) )
            {
                $productPrices = json_decode(file_get_contents($fileName),true);
            }
            else
            {
                $productPrices =  json_decode(file_get_contents( sprintf(self::EDX_ENROLLMENT_COURSE_DETAIL, $courseId) ),true);
                if(!empty($productPrices))
                {
                    file_put_contents($fileName, json_encode($productPrices));
                }
            }

            foreach( $productPrices['course_modes'] as $mode)
            {
                if($mode['name'] == 'Honor Certificate' || $mode['name'] == 'Audit')
                {
                    continue;
                }
                $this->out( $edxCourse['title'] . '|||' . $mode['name'] . '|||' . $mode['min_price']);
                fputcsv($fp, array(
                    $edxCourse['title'], $mode['name'], $mode['min_price']
                ));
            }
             continue;
             * */

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
                        // Add instructors
                        if(!empty($edxCourse['course-instructors']['course-staff']['staff-name']))
                        {
                            print_r( $edxCourse['course-instructors']['course-staff']['staff-name'] );
                            $insName = $edxCourse['course-instructors']['course-staff']['staff-name'];
                            $course->addInstructor($this->dbHelper->createInstructorIfNotExists($insName));
                        }
                        elseif( !empty( $edxCourse['course-instructors']['course-staff'] ))
                        {
                            foreach( $edxCourse['course-instructors']['course-staff'] as $staff )
                            {
                                $insName = $staff['staff-name'];
                                if(!empty($insName))
                                {
                                    $course->addInstructor($this->dbHelper->createInstructorIfNotExists($insName));
                                }
                            }
                        }

                        $em->persist($course);
                        $em->flush();

                        $tagService->saveCourseTags( $course, $cTags);

                        $this->dbHelper->sendNewCourseToSlack( $course, $this->initiative );

                        if($edxCourse['course-image-banner'])
                        {
                            $this->uploadImageIfNecessary( $edxCourse['course-image-banner'], $course);
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

                        if($edxCourse['course-image-banner'])
                        {
                            $this->uploadImageIfNecessary( $edxCourse['course-image-banner'], $dbCourse);
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
            $offering->setStartDate( new \DateTime( $edxCourse['course-start'] ) );

            if( empty($edxCourse['course-end']) )
            {
                // Put an end date for 4 weeks in the future
                $endDate = new \DateTime(   $edxCourse['course-start'] );
                $endDate->add(new \DateInterval("P30D") );
            }
            else
            {
                $endDate = new \DateTime(  $edxCourse['course-end'] );
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
        }
        /**
         fclose($fp);
         */
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
        $course->setShortName( 'edx_' . $c['key'] );
        $course->setInitiative( $this->initiative );
        $course->setName(  $c['title'] );
        $course->setDescription( $c['short_description'] );
        $course->setLongDescription( nl2br($c['full_description']) );
        $course->setLanguage( $defaultLanguage);
        $course->setStream($defaultStream); // Default to Computer Science
        $course->setUrl($c['marketing_url']);
        $course->setCertificate( false );
        $course->setCertificatePrice( 0 );

        if(!empty($c['video']['src']))
        {
            $course->setVideoIntro( $c['video']['src'] );
        }
        // Check if the video is in course runs
        foreach($c['course_runs'] as $courseRun)
        {
            if(!empty($courseRun['video']['src']))
            {
                $course->setVideoIntro( $courseRun['video']['src'] );
            }
            if(!empty($courseRun['marketing_url']) )
            {
                $course->setUrl($courseRun['marketing_url']);
            }

            foreach($courseRun['seats'] as $seat)
            {
                if($seat['type'] == 'verified')
                {
                    $course->setCertificatePrice( $seat['price'] );
                    $course->setCertificate( true );
                }
            }

            if($courseRun['pacing_type'] == 'instructor_paced')
            {
                $length = null;
                $start = new \DateTime( $courseRun['start'] );
                $end = new \DateTime( $courseRun['end'] );
                $length = ceil( $start->diff($end)->days/7 );
                $course->setDurationMin($length);
                $course->setDurationMax($length);
            }
        }

        return $course;
    }

    /**
     * Given an array built from edX csv returns a course entity
     * @param array $c
     */
    private function getCourseEntityFromDrupalAPI ($c = array())
    {

        $langMap = $this->dbHelper->getLanguageMap();
        $language = $langMap[ 'English' ];
        $edXLanguage = $c['language'];
        if($edXLanguage == 'Chinese - Mandarin')
        {
            $edXLanguage ='Chinese';
        }
        if( isset($langMap[$edXLanguage]))
        {
            $language = $langMap[$edXLanguage];
        }
        $stream = $this->dbHelper->getStreamBySlug('cs');

        foreach($c['course_page_info']['subjects'] as $sub)
        {
            if(isset($this->subjectsMap[ $sub['title'] ]))
            {
                $stream = $this->dbHelper->getStreamBySlug( $this->subjectsMap[ $sub['title'] ] );
            }
            break;
        }

        if($c['number'] =='CS50')
        {
            // CS50x and CS50AP both have the same number. So use the display_course_number
            $c['number'] = $c['course_page_info']['display_course_number'];
        }

        $shortName = strtolower('edx_'.$c['number'].'_'.$c['org']);

        $course = new Course();
        $course->setShortName( $shortName );
        $course->setInitiative( $this->initiative );
        $course->setIsMooc(true);
        $course->setName(  $c['title'] );
        $course->setDescription( $c['course_page_info']['subtitle'] );
        $course->setLongDescription( $c['course_page_info']['description'] );
        $course->setSyllabus( $c['course_page_info']['syllabus']);
        $course->setLanguage( $language);
        $course->setStream($stream); // Default to Computer Science
        $course->setUrl($c['marketing_url']);
        $course->setCertificate( false );
        $course->setCertificatePrice( 0 );

        foreach($c['course_page_info']['subjects'] as $sub)
        {
            if(isset($this->subjectsMap[ $sub['title'] ]))
            {
                $stream = $this->dbHelper->getStreamBySlug( $this->subjectsMap[ $sub['title'] ] );
            }
            break;
        }

        if(!empty($c['course_page_info']['video']['url']))
        {
            $course->setVideoIntro($c['course_page_info']['video']['url'] );
        }
        // Check if the video is in course runs
        if (isset($c['course_modes']['course_modes']))
        {
            foreach($c['course_modes']['course_modes'] as $courseMode)
            {

                if($courseMode['slug'] == 'verified')
                {
                    $course->setCertificatePrice( $courseMode['min_price'] );
                    $course->setCertificate( true );
                }
            }
        }

        // the course is archived. available to register, but certificates not available.
        if($c['availability'] =='Archived')
        {
            $course->setCertificate( false );
            $course->setCertificatePrice( 0 );
        }

        if(isset($c['course_runs']) && $c['course_runs']['type'] =='professional')
        {
            $course->setIsMooc(false);
            $course->setPrice(  $course->getCertificatePrice() );
            $course->setPricePeriod( Course::PRICE_PERIOD_TOTAL );
        }

        foreach( $c['course_page_info']['schools']  as $school)
        {
            $ins = $this->dbHelper->getInstitutionByName( trim($school['name']) );
            if($ins)
            {
                $course->addInstitution( $ins );
            }
        }

        return $course;
    }

    private function getOffering($c, Course $course)
    {
        $offering = new Offering();
        $now = new \DateTime();
        $run = $c['course_runs'];
        $offering->setShortName( 'edx_' . $run['uuid'] );
        $offering->setCourse( $course );
        $offering->setUrl( $c['marketing_url'] );
        $offering->setStatus( Offering::START_DATES_KNOWN );
        $offering->setStartDate( new \DateTime( $c['start'] ) );
        if(  !empty($c['end']) )
        {
            $offering->setEndDate(  new \DateTime(  $c['end'] ) );
        }
        else if( !empty($c['course_page_info']['end']) )
        {
            $offering->setEndDate(  new \DateTime(  $c['course_page_info']['end'] ) );
        }


        if($run['pacing_type'] == 'instructor_paced')
        {
            // Do nothing
        }
        elseif($run['pacing_type'] == 'self_paced')
        {
            if($now > $offering->getStartDate())
            {
                $offering->setStatus( Offering::COURSE_OPEN );
            }
            else
            {
                $offering->setStatus( Offering::START_DATES_KNOWN );
            }
        }

        // the course is archived. available to register, but certificates not available.
        if($c['availability'] =='Archived')
        {
            $offering->setStatus( Offering::COURSE_OPEN );
        }

        if( !empty($c['enrollment_end']) )
        {
            $enrollmentEndDate = new \DateTime($c['enrollment_end']);

            if( ($now > $enrollmentEndDate) && $offering->getStatus() == Offering::COURSE_OPEN)
            {
                // This will make the course show not as self paced.
                $offering->setStatus( Offering::START_DATES_KNOWN );
                $this->out( "Offering is not self paced: " .$course->getName());
            }
        }

        return $offering;
    }


    private function getShortName( $details )
    {
        $school = $details['course-school'];
        if(is_array($details['course-school']))
        {
            $school = array_pop( $details['course-school'] );
        }
        return 'edx_' . strtolower( $details['course-code'] . '_' . $school );
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
            file_put_contents($filePath,$this->file_get_contents_wrapper($imageUrl));
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

                    // Check how many of them are self paced
                    $selfPaced = false;
                    if ( $dbCourse->getNextOffering()->getStatus() == Offering::COURSE_OPEN )
                    {
                        $selfPaced = true;
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

    public function scrapeCredentials()
    {


        foreach(self::$EDX_XSERIES_GUID as $guid)
        {
            $xseries = json_decode($this->file_get_contents_wrapper(
                sprintf( 'https://www.edx.org/node/%s.json?deep-load-refs=1',$guid )),
                true);
            $credential = $this->getCredential($xseries);
            $this->saveOrUpdateCredential( $credential, $xseries['field_xseries_banner_image']['file']['uri'] );
        }

        /**
        $edXCourses = json_decode(file_get_contents( 'https://www.edx.org/search/api/all' ),true);
        foreach($edXCourses as $edXCourse)
        {
             if(in_array('xseries',$edXCourse['types']))
             {
                $this->out( $edXCourse['l'] );
                 $guid = $edXCourse['guid'];
                 var_dump($guid);
                 continue;
                 $xseries = json_decode(file_get_contents(
                     sprintf( 'https://www.edx.org/node/%s.json?deep-load-refs=1',$guid )),
                     true);



             }
        }

        return;

        $edXCourses = json_decode(file_get_contents( self::EDX_CARDS_API ),true);
        foreach($edXCourses as $edXCourse)
        {
            if( isset($edXCourse['attributes']['xseries'] ) )
            {
                $this->out($edXCourse['title']);
                $xseriesId = $edXCourse['attributes']['xseries'];

                var_dump(  $edXCourse['attributes'] );
            }
        }
         */
    }


    public function getCredential( $xseries )
    {
        $credential = new Credential();
        $credential->setName( $xseries['title'] );
        $credential->setPricePeriod(Credential::CREDENTIAL_PRICE_PERIOD_TOTAL);
        $credential->setPrice(0);
        $credential->setSlug( UniversalHelper::getSlug( $credential->getName()) . '-xseries'   );
        $credential->setInitiative( $this->initiative );
        $credential->setUrl( $xseries['url'] );
        $credential->setOneLiner( $xseries['field_xseries_subtitle'] );
        $credential->setSubTitle( $xseries['field_xseries_subtitle_short'] );
        $credential->setDescription( $xseries['body']['value'] .  $xseries['field_xseries_overview']['value'] );

        return $credential;
    }

    /**
     * @param Credential $credential
     */
    private function saveOrUpdateCredential(Credential $credential, $imageUrl)
    {
        $dbCredential = $this->dbHelper->getCredentialBySlug( $credential->getSlug() ) ;
        $em = $this->getManager();
        if( !$dbCredential )
        {
            if($this->doCreate())
            {
                $this->out("New Credential - " . $credential->getName() );
                if ($this->doModify())
                {
                    $em->persist( $credential );
                    $em->flush();

                    $this->dbHelper->uploadCredentialImageIfNecessary($imageUrl,$credential);
                }
            }
        }
        else
        {
            // Update the credential
            $changedFields = $this->dbHelper->changedFields($this->credentialFields,$credential,$dbCredential);
            if(!empty($changedFields) && $this->doUpdate())
            {
                $this->out("UPDATE CREDENTIAL - " . $dbCredential->getName() );
                $this->outputChangedFields( $changedFields );
                if ($this->doModify())
                {
                    $em->persist($dbCredential);
                    $em->flush();

                    $this->dbHelper->uploadCredentialImageIfNecessary($imageUrl,$dbCredential);
                }
            }

        }
    }

    private function getAccessToken()
    {

        $clientId = $this->getContainer()->getParameter('edx_api_client_id');
        $clientSecret = $this->getContainer()->getParameter('edx_api_client_secret');
        $client = new Client([
            'base_uri' => self::EDX_API_ALL_COURSES_BASE_v1,
            'timeout'  => 2.0,
        ]);

        $response = $client->post('/oauth2/v1/access_token',[
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id'=>$clientId,
                'client_secret' => $clientSecret,
                'token_type' => 'jwt'
            ]
        ]);

        $r = json_decode($response->getBody(),true);
        return $r['access_token'];
    }

    private function getOldShortName($key)
    {
        $keyParts = explode('+',$key);
        $keyParts[] = 'edx';
        $keyParts = array_reverse($keyParts);

        return strtolower( implode( '_',$keyParts ));
    }




    private function getOfferingFromCourseRun($run,$course)
    {
        $offering = new Offering();
        $now = new \DateTime();

        $offering->setShortName( 'edx_' . $run['key'] );
        $offering->setCourse( $course );
        $offering->setUrl( $run['marketing_url'] );
        $offering->setStatus( Offering::START_DATES_KNOWN );
        $offering->setStartDate( new \DateTime( $run['start'] ) );
        $offering->setEndDate(  new \DateTime(  $run['end'] ) );

        if($run['pacing_type'] == 'instructor_paced')
        {
            // Do nothing
        }
        elseif($run['pacing_type'] == 'self_paced')
        {
            if($now > $offering->getStartDate())
            {
                $offering->setStatus( Offering::COURSE_OPEN );
            }
            else
            {
                $offering->setStatus( Offering::START_DATES_KNOWN );
            }
        }


        return $offering;
    }

    public function getedXJson($client, $nextUrl)
    {
        $today = new \DateTime();
        $today = $today->format('Y_m_d');
        $filename =md5($nextUrl) . "_$today.json";
        $filePath = '/tmp/'.$filename;
        $edXCourses = null;
        if(file_exists($filePath))
        {
            $edXCourses = file_get_contents($filePath);
            $this->out("Read from cache");
        }
        else
        {
            try {
                $response = $client->get($nextUrl, [
                    'headers' => [
                        'Authorization' => "JWT {$this->getAccessToken()}"
                    ]
                ]);
                $edXCourses = $response->getBody();
                file_put_contents($filePath,$edXCourses);
            } catch(\Exception $e)
            {
                $this->out($e->getMessage()); exit();
            }
        }

        return $edXCourses;
    }

    public function getEdxDrupalJson()
    {
        $today = new \DateTime();
        $today = $today->format('Y_m_d');

        $filename = "edx_$today.json";
        $filePath = '/tmp/'.$filename;
        $edXCourses = array();
        if(file_exists($filePath))
        {

            $edXCourses = json_decode($this->file_get_contents_wrapper($filePath),true);
            $this->out("Read from cache");
        }
        else
        {
            $allCourses =  json_decode( $this->file_get_contents_wrapper(self::EDX_DRUPAL_CATALOG),true);
            foreach($allCourses['objects']['results'] as $edXCourse)
            {
                $this->out($edXCourse['title']);
                $edXCourse['course_page_info'] =  json_decode( $this->file_get_contents_wrapper(sprintf(self::EDX_DRUPAL_INDIVIDUAL,$edXCourse['key'])),true);
                $edXCourse['course_runs'] = json_decode( $this->file_get_contents_wrapper(sprintf(self::EDX_DRUPAL_COURSE_RUNS,$edXCourse['key'])),true);
                $edXCourse['course_modes'] = json_decode( $this->file_get_contents_wrapper(sprintf(self::EDX_DRUPAL_COURSE_MODES,$edXCourse['key'])),true);

                $edXCourses[] = $edXCourse;
            }
            file_put_contents($filePath,json_encode($edXCourses));
        }
        return $edXCourses;
    }

    /**
     * Wrapper to catch exceptions in file_get_contents
     * @param $url
     */
    private function file_get_contents_wrapper($url)
    {
        try
        {
            return file_get_contents($url);
        } catch (\Exception $e)
        {
            $this->out("file_get_contents_error: " . $e->getMessage());
        }

        return '';
    }


}