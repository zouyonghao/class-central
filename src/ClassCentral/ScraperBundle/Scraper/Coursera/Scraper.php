<?php

namespace ClassCentral\ScraperBundle\Scraper\Coursera;

use ClassCentral\CredentialBundle\Entity\Credential;
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
    const ONDEMAND_COURSE_URL = 'https://www.coursera.org/api/onDemandCourses.v1?fields=partners.v1(squareLogo,rectangularLogo),instructors.v1(fullName),overridePartnerLogos&includes=instructorIds,partnerIds,_links&&q=slug&slug=%s';

    // Contains courses schedule
    const ONDEMAND_OPENCOURSE_API = 'https://www.coursera.org/api/opencourse.v1/course/%s?showLockedItems=true';
    CONST ONDEMAND_COURSE_SCHEDULE = 'https://www.coursera.org/api/onDemandCourseSchedules.v1/%s/?fields=defaultSchedule';
    const ONDEMAND_COURSE_MATERIALS = 'https://www.coursera.org/api/onDemandCourseMaterials.v1/?q=slug&slug=%s&includes=moduleIds,lessonIds,itemIds,tracks&fields=moduleIds,onDemandCourseMaterialModules.v1(name,slug,description,timeCommitment,lessonIds,optional),onDemandCourseMaterialLessons.v1(name,slug,timeCommitment,itemIds,optional,trackId),onDemandCourseMaterialItems.v1(name,slug,timeCommitment,content,isLocked,lockableByItem,itemLockedReasonCode,trackId),onDemandCourseMaterialTracks.v1(passablesCount)&showLockedItems=true';

    const ONDEMAND_SESSION_IDS = 'https://www.coursera.org/api/onDemandSessions.v1/?q=currentOpenByCourse&courseId=%s&includes=memberships&fields=moduleDeadlines';

    const COURSE_CATALOG_URL_v2 = 'https://www.coursera.org/api/catalogResults.v2?q=search&query=&limit=5000&debug=false&fields=debug,courseId,domainId,onDemandSpecializationId,specializationId,subdomainId,suggestions,courses.v1(name,description,slug,photoUrl,courseStatus,partnerIds),onDemandSpecializations.v1(name,description,slug,logo,courseIds,launchedAt,partnerIds),specializations.v1(name,description,shortName,logo,primaryCourseIds,display,partnerIds),partners.v1(name)&includes=courseId,domainId,onDemandSpecializationId,specializationId,subdomainId,suggestions,courses.v1(partnerIds),onDemandSpecializations.v1(partnerIds),specializations.v1(partnerIds)';
    const COURSE_FACILITATED_GROUPS = 'https://www.coursera.org/api/onDemandFacilitatedGroups.v1/?q=firstAvailableInScope&scopeId=session~%s!~%s&fields=groupId,scopeId,facilitators,mentorProfiles.v1(fullName,bio,email,photoUrl,title,social),onDemandFacilitatedGroupAvailabilities.v1(spotsTaken,spotsLeft,memberLimit,hasAvailability)&includes=mentorProfiles,availability';

    // CREDENTIAL_URS
    const SPECIALIZATION_CATALOG_URL = 'https://www.coursera.org/api/specializations.v1';
    const SPECIALIZATION_URL  = 'https://www.coursera.org/maestro/api/specialization/info/%s?currency=USD&origin=US';
    const SPECIALIZATION_PAGE_URL = 'https://www.coursera.org/specialization/%s/%s?utm_medium=classcentral';

    const SPECIALIZATION_ONDEMAND_CATALOG_URL = 'https://www.coursera.org/api/onDemandSpecializations.v1';
    const SPECIALIZATION_ONDEMAND_URL = 'https://www.coursera.org/api/onDemandSpecializations.v1?fields=capstone,courseIds,description,instructorIds,interchangeableCourseIds,logo,metadata,partnerIds,partnerLogoOverrides,tagline,partners.v1(description,name,squareLogo),instructors.v1(firstName,lastName,middleName,partnerIds,photo,prefixName,profileId,shortName,suffixName,title),courses.v1(courseProgress,courseType,description,instructorIds,membershipIds,name,startDate,subtitleLanguages,v1Details,vcMembershipIds,workload),v1Details.v1(courseSyllabus),memberships.v1(grade,vcMembershipId),vcMemberships.v1(certificateCodeWithGrade)&includes=courseIds,instructorIds,partnerIds,instructors.v1(partnerIds),courses.v1(courseProgress,instructorIds,membershipIds,subtitleLanguages,v1Details,vcMembershipIds)&q=slug&slug=%s';
    const SPECIALIZATION_ONDEMAND_PAGE_URL = 'https://www.coursera.org/specializations/%s?utm_medium=classcentral';

    const PRODUCT_PRICES = 'https://www.coursera.org/api/productPrices.v3/VerifiedCertificate~%s~USD~US';
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
        "zh-TW" => "Chinese",
        "zh-CN" => "Chinese",
        "zh"    => "Chinese",
        "ar" => "Arabic",
        "ru" => "Russian",
        "tr" => "Turkish",
        "he" => "Hebrew",
        'pt-br' => 'Portuguese',
        'pt-BR' => 'Portuguese',
        'pt' => 'Portuguese'
    );

    private $courseFields = array(
        'Url', 'SearchDesc', 'Description', 'Name', 'Language','LongDescription','Syllabus', 'WorkloadMin', 'WorkloadMax','WorkloadType',
        'Certificate','CertificatePrice' ,'VideoIntro','DurationMin','DurationMax'
    );

    private $onDemandCourseFields = array(
        'Url', 'Description', 'Name', 'Language','LongDescription','Syllabus',
        'Certificate','CertificatePrice','DurationMin','DurationMax'
    );

    private $credentialFields = array(
        'Url','Description','Name', 'OneLiner', 'SubTitle'
    );


    private $offeringFields = array(
        'StartDate', 'EndDate', 'Status','Url','ShortName'
    );

    public static $credentialSlugs = array(
        'computer-fundamentals' => 'fundamentalscomputing2',
        'data-mining' => 'datamining',
        'cyber-security' => 'cybersecurity',
        'virtual-teacher' => 'virtualteacher',
        'computational-biology' => 'bioinformatics',
        'content-strategy' => 'contentstrategy'
    );

    public function scrape()
    {
        if($this->isCredential)
        {
            $this->scrapeCredentials();
            return;
        }
        $defaultStream = $this->dbHelper->getStreamBySlug('cs');
        $dbLanguageMap = $this->dbHelper->getLanguageMap();
        $em = $this->getManager();
        $kuber = $this->container->get('kuber'); // File Api
        $offerings = array();


        //$this->buildOnDemandCoursesList();
        /*************************************
         * On Demand Courses
         *************************************/
        //$url = 'https://www.coursera.org/api/courses.v1';
        $url = self::COURSE_CATALOG_URL_v2;
        $allCourses = json_decode(file_get_contents( $url ),true);
        $fp = fopen("extras/course_prices.csv", "w");
        fputcsv($fp, array(
            'Course Name', 'Prices(in $)'
        ));
        foreach ($allCourses['linked']['courses.v1'] as $element)
        {
            if( $element['courseType'] == 'v2.ondemand' || $element['courseType'] == 'v2.capstone')
            {

                /**
                $productPrices =  json_decode(file_get_contents( sprintf(self::PRODUCT_PRICES, $element['id']) ),true);
                if( !empty($productPrices) )
                {
                    $this->out( $element['name'] . ' - ' . $productPrices['elements'][0]['amount'] );
                    fputcsv($fp,array($element['name'], $productPrices['elements'][0]['amount']));
                }
                 * */

                // On Demand Course Materials
                /**
                $onDemandCourseMaterials =  json_decode(file_get_contents( sprintf(self::ONDEMAND_COURSE_MATERIALS, $element['slug']) ),true);
                foreach( $onDemandCourseMaterials['linked']['onDemandCourseMaterialTracks.v1'] as $track)
                {
                    if($track['id'] == 'honors')
                    {
                        $this->out( $element['name'] );
                    }
                }
                **/

                $onDemandCourse =  json_decode(file_get_contents( sprintf(self::ONDEMAND_COURSE_URL, $element['slug']) ),true);
                //$this->out( $onDemandCourse['elements'][0]['name']  );

                if( !$onDemandCourse['elements'][0]['isReal'] )
                {
                    continue; //skip
                }
                $c = $this->getOnDemandCourse( $onDemandCourse );


                /**

                // Details for mentor sessions
                $courseId = $onDemandCourse['elements'][0]['id'];
                $sessionDetails = '';
                try{
                    $sessionDetails =  json_decode(file_get_contents( sprintf(self::ONDEMAND_SESSION_IDS,$courseId) ),true);
                } catch(\Exception $e) {
                    continue;
                }

                if(!empty($sessionDetails['elements']))
                {
                    foreach( $sessionDetails['elements'] as $session ) {
                        $sessionId = $session['id'];
                        $groupsUrl = sprintf(self::COURSE_FACILITATED_GROUPS, $courseId,$sessionId);
                        $groups = json_decode(file_get_contents($groupsUrl),true);
                        if(!empty($groups['elements']))
                        {
                            $this->out("MENTORED COURSE - " . $c->getName() );
                            $this->out ( $groups['linked']['onDemandFacilitatedGroupAvailabilities.v1'][0]['spotsTaken']);
                        }
                    }
                }
                continue;
                **/


                $dbCourse = null;
                $dbCourseFromSlug = $this->dbHelper->getCourseByShortName( $c->getShortName() );
                if( $dbCourseFromSlug  )
                {
                    $dbCourse = $dbCourseFromSlug;
                }
                else
                {
                    $dbCourseFromName = $this->findCourseByName($c->getName(), $this->initiative );
                    if($dbCourseFromName)
                    {
                        $dbCourse = $dbCourseFromName;
                    }
                }

                if( empty($dbCourse) )
                {
                    // Create the course
                    if($this->doCreate())
                    {
                        $this->out("NEW COURSE - " . $c->getName());

                        // NEW COURSE
                        if ($this->doModify())
                        {
                            $em->persist($c);
                            $em->flush();


                            if( $onDemandCourse['elements'][0]['promoPhoto'] )
                            {
                                $this->uploadImageIfNecessary( $onDemandCourse['elements'][0]['promoPhoto'], $c);
                            }

                            // Send an update to Slack
                            $this->dbHelper->sendNewCourseToSlack( $c, $this->initiative );
                            $dbCourse = $c;
                        }
                    }
                }
                else
                {
                    // Update the course details
                    $changedFields = $this->dbHelper->changedFields($this->onDemandCourseFields,$c,$dbCourse);
                    if(!empty($changedFields) && $this->doUpdate())
                    {
                        $this->out("UPDATE COURSE - " . $dbCourse->getName() );
                        $this->outputChangedFields( $changedFields );
                        if ($this->doModify())
                        {
                            $em->persist($dbCourse);
                            $em->flush();

                            $this->uploadImageIfNecessary($onDemandCourse['elements'][0]['promoPhoto'],$dbCourse);
                        }
                    }

                    // Check how many of them are self paced
                    $selfPaced = false;

                    if ( $dbCourse->getNextOffering()->getStatus() == Offering::COURSE_OPEN )
                    {
                        $selfPaced = true;
                    }
                    else
                    {
                        /*
                        if( isset($onDemandCourse['elements'][0]['plannedLaunchDate']))
                        {
                            $now = new \DateTime();
                            try{
                                $startDate = new \DateTime( $onDemandCourse['elements'][0]['plannedLaunchDate'] );
                            }
                            catch(\Exception $e)
                            {
                                $startDate = new \DateTime();
                            }

                            if( $startDate != $dbCourse->getNextOffering()->getStartDate() )
                            {

                                if ($this->doModify())
                                {
                                    $o = $dbCourse->getNextOffering();
                                    $o->setStartDate( $startDate );
                                    $o->setStatus( Offering::START_MONTH_KNOWN );
                                    $em->persist( $o );
                                    $em->flush();

                                    $this->out("OnDemand Course Updated Start Date : " . $element['name']) ;

                                }
                                
                            }
                            else if ( $now >= $dbCourse->getNextOffering()->getStartDate() )
                            {
                                if ($this->doModify())
                                {
                                    //Update the course to be self paced
                                    $o = $dbCourse->getNextOffering();
                                    $o->setStatus( Offering::COURSE_OPEN );
                                    $em->persist( $o );
                                    $em->flush();

                                    $this->out("OnDemand Course Updated to Self paced : " . $element['name']) ;                                }

                            }
                            $selfPaced = true;
                        }
                        */
                    }


                    // Update the sessions.
                    $courseId = $onDemandCourse['elements'][0]['id'];
                    $sessionDetails =  json_decode(file_get_contents( sprintf(self::ONDEMAND_SESSION_IDS,$courseId) ),true);
                    if(empty($sessionDetails['elements']))
                    {
                        // Create an offering
                        $offering = new Offering();
                        $offering->setShortName( $dbCourse->getShortName() );
                        $offering->setUrl( $dbCourse->getUrl() );
                        $offering->setCourse( $dbCourse );

                        if( isset($onDemandCourse['elements'][0]['plannedLaunchDate']))
                        {
                            try
                            {
                                // Self paced Not Started - But will Start in the future
                                $this->out("SELF PACED FUTURE COURSE : " . $dbCourse->getName() );
                                $startDate = new \DateTime( $onDemandCourse['elements'][0]['plannedLaunchDate'] );
                                $endDate =  new \DateTime(  $onDemandCourse['elements'][0]['plannedLaunchDate']  );
                                $endDate->add( new \DateInterval("P30D") );
                                $offering->setStatus( Offering::START_DATES_KNOWN );
                            }
                            catch(\Exception $e)
                            {
                                continue;
                            }
                        }
                        else
                        {
                            // Self paced course that can be accessed right now
                            $this->out("SELF PACED COURSE : " . $dbCourse->getName() );
                            $startDate = new \DateTime();
                            $offering->setStatus( Offering::COURSE_OPEN );
                            $endDate =  new \DateTime( );
                            $endDate->add( new \DateInterval("P30D") );

                            if($dbCourse->getNextOffering()->getStatus() == Offering::COURSE_OPEN )
                            {
                                // Already self paced nothing to be done here
                                continue;
                            }
                        }

                        $offering->setStartDate( $startDate );
                        $offering->setEndDate( $endDate );

                        // Check if offering exists
                        $dbOffering = $this->dbHelper->getOfferingByShortName( $dbCourse->getShortName() );

                        if($dbOffering)
                        {
                            // Check if the dates and other details are right
                            $this->offeringChangedFields($offering,$dbOffering);
                        }
                        else
                        {
                            // Save and Create the offering
                            if($this->doCreate())
                            {
                                $this->out("NEW OFFERING - " . $offering->getName() );
                                if ($this->doModify())
                                {
                                    $em->persist($offering);
                                    $em->flush();
                                    $this->dbHelper->sendNewOfferingToSlack( $offering);
                                }

                            }
                        }
                    }
                    else
                    {

                        $dbOffering = null;
                        // Regularly Scheduled Course
                        $this->out("Regularly Scheduled Course : " . $dbCourse->getName() );
                        foreach($dbCourse->getOfferings() as $o)
                        {
                           if( $o->getShortName() == $dbCourse->getShortName() )
                           {
                               $dbOffering = $o; // A course with future announced date becomes current and has sessions
                               break;
                           }
                        }
                        foreach( $sessionDetails['elements'] as $session )
                        {
                            $sessionId = $session['id'];
                            $offeringShortName = 'coursera_' . $sessionId;
                            // Create an offering
                            $offering = new Offering();
                            $offering->setShortName( $offeringShortName );
                            $offering->setUrl( $dbCourse->getUrl() );
                            $offering->setCourse( $dbCourse );
                            $offering->setStatus( Offering::START_DATES_KNOWN );

                            $startDate = new \DateTime( '@'. intval($session['startedAt']/1000) );
                            $endDate =new \DateTime( '@'. intval($session['endedAt']/1000) );
                            $startDate->setTimezone( new \DateTimeZone('America/Los_Angeles') );
                            $endDate->setTimezone( new \DateTimeZone('America/Los_Angeles') );

                            $offering->setStartDate( $startDate );
                            $offering->setEndDate( $endDate );

                            // Check if offering exists
                            if(!$dbOffering)
                            {
                                $dbOffering = $this->dbHelper->getOfferingByShortName( $offeringShortName );
                            }
                            if($dbOffering)
                            {
                                // Check if the dates and other details are right
                                $this->offeringChangedFields($offering,$dbOffering);
                            }
                            else
                            {
                                if($this->doCreate())
                                {
                                    $this->out("NEW OFFERING - " . $offering->getName() );
                                    if ($this->doModify())
                                    {
                                        $em->persist($offering);
                                        $em->flush();
                                        $this->dbHelper->sendNewOfferingToSlack( $offering);
                                    }
                                }
                            }
                            $dbOffering = null;
                        }
                    }


                    if( !$selfPaced )
                    {
                        //$this->out("OnDemand Session Missing : " . $element['name']) ;
                    }
                }
            }
        }
        fclose($fp);
        /*************************************
         * Session Based Courses
         *************************************/
        $courseraCourses = $this->getCoursesArray();
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
                $course->setLanguage($dbLanguageMap['English']); // Use default language english
            }

            // Get the workload
            if( !empty($catalogDetails['estimatedClassWorkload']) && $workload = $this->getWorkLoad($catalogDetails['estimatedClassWorkload']) )
            {
                $course->setWorkloadType(Course::WORKLOAD_TYPE_HOURS_PER_WEEK);
                $course->setWorkloadMin( $workload[0] );
                $course->setWorkloadMax( $workload[1] );
            }

            // Get the certificate information
            $sid = $this->getLatestSessionId( $catalogDetails );
            if( $sid )
            {
                $sDetails  = $this->getDetailsFromSessionCatalog( $sid );
                $course->setCertificate( $sDetails['eligibleForCertificates'] || $sDetails['eligibleForSignatureTrack'] );
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
                $courseLength = $this->getOfferingLength($newestOffering['duration_string']);
                $course->setDurationMin($courseLength);
                $course->setDurationMax($courseLength);
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

    private function offeringChangedFields($offering, $dbOffering )
    {
        $offeringModified = false;
        $changedFields = array();
        $em = $this->getManager();
        foreach ($this->offeringFields as $field)
        {
            $getter = 'get' . $field;
            $setter = 'set' . $field;

            $oldValue =  $dbOffering->$getter();
            $newValue = $offering->$getter();

            // Date comparision fails due to different time zones
            if( gettype($oldValue) == 'object' && get_class($oldValue) == 'DateTime')
            {
                $oldValue =  $dbOffering->$getter()->format('jS M, Y');
                $newValue = $offering->$getter()->format('jS M, Y');
            }

            if ( $oldValue  !=  $newValue)
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
        }
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



    private function getOnDemandCourse( $data = array() )
    {
        $dbLanguageMap = $this->dbHelper->getLanguageMap();

        $course = new Course();
        $course->setShortName( substr('coursera_' . $data['elements'][0]['slug'], 0, 49));
        $course->setInitiative($this->initiative);
        $course->setName( $data['elements'][0]['name'] );
        $course->setDescription( $data['elements'][0]['description'] );
        $course->setLongDescription( nl2br($data['elements'][0]['description']) );
        $course->setStream(  $this->dbHelper->getStreamBySlug('cs') ); // Default to Computer Science
        $course->setUrl( 'https://www.coursera.org/learn/'. $data['elements'][0]['slug']);

        $lang = self::$languageMap[ $data['elements']['0']['primaryLanguageCodes'][0] ];
        if(isset( $dbLanguageMap[$lang] ) ) {
            $course->setLanguage( $dbLanguageMap[$lang] );
        } else {
            $this->out("Language not found " . $data['elements']['0']['primaryLanguageCodes'][0] );
            $course->setLanguage($dbLanguageMap['English']); // Use default language english
        }

        $course->setCertificate( $data['elements'][0]['isVerificationEnabled'] );
        $course->setCertificatePrice(Course::PAID_CERTIFICATE); // Price not known. Signify paid certificate.

        // Add the university
        foreach ($data['linked']['partners.v1'] as $university)
        {
            $ins = new Institution();
            $ins->setName($university['name']);
            $ins->setIsUniversity(true);
            $ins->setSlug($university['shortName']);
            $course->addInstitution($this->dbHelper->createInstitutionIfNotExists($ins));
        }

        foreach ( $data['linked']['instructors.v1'] as $courseraInstructor)
        {
            if(!empty( $courseraInstructor['fullName'] ) )
            {
                $insName = $courseraInstructor['fullName'] ;
            }
            else
            {
                $insName = $courseraInstructor['firstName'] . ' ' . $courseraInstructor['lastName'];
            }

            $course->addInstructor($this->dbHelper->createInstructorIfNotExists($insName));
        }


        // Get Course Details like Syllabus and length
        $courseDetails =  json_decode(file_get_contents( sprintf(self::ONDEMAND_OPENCOURSE_API, $data['elements'][0]['slug']) ),true);
        if( !empty($courseDetails) )
        {
            $syllabus = '';
            foreach($courseDetails['courseMaterial']['elements'] as $item)
            {
                $syllabus .= "<b>{$item['name']}</b><br/>{$item['description']}<br/><br/>";

            }
            $course->setSyllabus( $syllabus);
        }

        // Calculate the length of the course
        $schedule = json_decode(file_get_contents( sprintf(self::ONDEMAND_COURSE_SCHEDULE, $data['elements'][0]['id']) ),true);
        if( !empty($schedule) )
        {
            $length = 0;
            foreach( $schedule['elements'][0]['defaultSchedule']['periods'] as $period)
            {
                $length += $period['numberOfWeeks'];
            }

            if($length > 0)
            {
                // Length of the course in weeks
                $course->setDurationMin($length);
                $course->setDurationMax($length);
            }
        }


        return $course;
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

    public function scrapeCredentials()
    {
//        $specializations = json_decode(file_get_contents( self::SPECIALIZATION_CATALOG_URL ),true);
//        foreach($specializations['elements'] as $item)
//        {
//            $details = json_decode(file_get_contents( sprintf(self::SPECIALIZATION_URL, $item['id']) ),true);
//
//            $credential =$this->getCredentialFromSpecialization( $details );
//            $this->saveOrUpdateCredential( $credential, $details['logo'] );
//        }

        // Scrape Ondemand specializations
        $onDemandSpecializations = json_decode(file_get_contents( self::SPECIALIZATION_ONDEMAND_CATALOG_URL ),true);
        foreach( $onDemandSpecializations['elements'] as $item )
        {
            $details = json_decode(file_get_contents( sprintf(self::SPECIALIZATION_ONDEMAND_URL, $item['slug']) ),true);
            $credential = $this->getCredentialFromOnDemandSpecialization( $details );
            $this->saveOrUpdateCredential( $credential, $details['elements'][0]['logo'] );
        }
    }

    private function getCredentialFromOnDemandSpecialization($details )
    {
        $credential = new Credential();

        $credential->setName( $details['elements'][0]['name'] );
        $credential->setPricePeriod(Credential::CREDENTIAL_PRICE_PERIOD_TOTAL);
        $credential->setPrice(0);
        if( isset(self::$credentialSlugs[$details['elements'][0]['slug'] ]))
        {
            $details['elements'][0]['slug']  = self::$credentialSlugs[$details['elements'][0]['slug'] ];
        }
        $credential->setSlug( $details['elements'][0]['slug'] . '-specialization' );
        $credential->setInitiative( $this->initiative );
        $credential->setUrl( sprintf(self::SPECIALIZATION_ONDEMAND_PAGE_URL,$details['elements'][0]['slug']));
        $credential->setOneLiner( $details['elements'][0]['metadata']['subheader'] );

        if( isset($details['elements'][0]['metadata']['headline']) )
        {
            $credential->setSubTitle(  $details['elements'][0]['metadata']['headline'] );
        }
        else
        {
            echo  $details['elements'][0]['tagline']."\n";
            $credential->setSubTitle(  $details['elements'][0]['tagline'] );
        }


        // Add the institutions
        foreach( $details['linked']['partners.v1'] as $university )
        {
            $ins = $this->dbHelper->getInstitutionBySlug( $university['shortName']);
            if($ins)
            {
                $credential->addInstitution( $ins );
            }
            else
            {
                $this->out("University Not Found - " . $university['name']);
            }
        }

        // Add the courses
        foreach($details['linked']['courses.v1'] as $topic )
        {
            $course = $this->dbHelper->getCourseByShortName( 'coursera_' . $topic['slug'] );
            if( $course )
            {
                $credential->addCourse( $course );
            }
            else
            {
               $this->out("Course Not Found - " . $topic['name']);
            }
        }

        // Build the description
        $description = $details['elements'][0]['description'];
        $incentives = $details['elements'][0]['metadata']['incentives'];
        $learningObjectives = '';
        foreach($details['elements'][0]['metadata']['learningObjectives'] as $objective)
        {
            $learningObjectives .= "<li>$objective</li>";
        }
        $recommendedBackground = '';
        foreach($details['elements'][0]['metadata']['recommendedBackground'] as $background)
        {
            $recommendedBackground .= "<li>$background</li>";
        }

        $credential->setDescription(
            "<p>$description</p>" .
            "<h3 class='table-tab-content__title'>Incentives & Benefits</h3><p>$incentives</p>".
            "<h3 class='table-tab-content__title'>What You'll Learn</h3>" ."<p><ul>$learningObjectives</ul></p>".
            "<h3 class='table-tab-content__title'>Recommended Background</h3>" . "<p><ul>$recommendedBackground</ul></p>"
        );

        return $credential;
    }

    private function getCredentialFromSpecialization( $details )
    {
        $credential = new Credential();
        $credential->setName( $details['name'] );
        $credential->setPricePeriod(Credential::CREDENTIAL_PRICE_PERIOD_TOTAL);
        $credential->setPrice(0);
        $credential->setSlug( $details['short_name']. '-specialization' );
        $credential->setInitiative( $this->initiative );
        $credential->setUrl( sprintf(self::SPECIALIZATION_PAGE_URL,$details['short_name'], $details['id']));
        $credential->setOneLiner( $details['subhead']);

        // Add the institutions
        foreach( $details['universities'] as $university )
        {
            $ins = $this->dbHelper->getInstitutionBySlug( $university['short_name']);
            if($ins)
            {
                $credential->addInstitution( $ins );
            }
            else
            {
                $this->out("University Not Found - " . $university['name']);
            }
        }

        // Add the courses
        foreach($details['topics'] as $topic )
        {
            $course = $this->dbHelper->getCourseByShortName( 'coursera_' . $topic['short_name'] );
            if( $course )
            {
                $credential->addCourse( $course );
            }
            else
            {
                $this->out("Course Not Found - " . $topic['name']);
            }
        }

        // Get Description
        $credential->setDescription( $details['byline'] );

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

}