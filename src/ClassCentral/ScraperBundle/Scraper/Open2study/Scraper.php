<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dhawal
 * Date: 3/24/13
 * Time: 12:59 AM
 * To change this template use File | Settings | File Templates.
 */

namespace ClassCentral\ScraperBundle\Scraper\Open2study;
use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Entity\Course;
use Symfony\Component\Validator\Constraints\Date;

class Scraper extends ScraperAbstractInterface
{
    const COURSE_CATALOGUE = 'https://www.open2study.com/courses';
    const BASE_URL = 'https://www.open2study.com/';
    const API_URL = 'https://www.open2study.com/courses.xml';
    public static $SELF_PACED_COURSES = array(899,904,1645);
    public static $NEXT_SESSION_START_DATE = '2016-03-21';

    public function scrape()
    {
        $this->out("Scraping " . $this->initiative->getName());
        $em = $this->getManager();
        $courses = file_get_contents(sprintf(self::API_URL));
        $simpleXml = simplexml_load_string($courses,'SimpleXMLElement', LIBXML_NOCDATA);
        $courses = json_decode(json_encode((array)$simpleXml), TRUE);

        foreach($courses['node'] as $node)
        {
            $name = trim($node['Title']);

            // Get the course
            $course = $this->dbHelper->findCourseByName($name,$this->initiative);
            if($course)
            {
                if ($node['Status'] && $node['Status'] == 'selfpaced')
                {
                    // Do nothing
                    $this->out("'$name' is self paced");
                }
                else
                {
                    // Get Offering
                    $offering = new Offering();
                    $offering->setCourse($course);
                    $offering->setStatus(Offering::START_DATES_KNOWN);
                    $offering->setStartDate( \DateTime::createFromFormat('U', $node['startdateunix']) );
                    $offering->setEndDate( \DateTime::createFromFormat('U', $node['enddateunix']) );
                    $offering->setShortName( 'open2study_' . $course->getId() . '_' . $node['startdate'] );
                    $offering->setUrl( 'https://www.open2study.com' . $node['Path'] );

                    $dbOffering = $this->dbHelper->getOfferingByShortName( $offering->getShortName() );
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
                }

            }
            else
            {
                $this->out("'$name' not found");
            }
        }

        exit();

        $em = $this->getManager();
        // Array of offerings created or updated
        $offerings = array();

        $this->out("Scraping " . $this->initiative->getName());
        /**
         * No new couress are being added. So commented out
        // Step 1: Getting a list of course URLs
        $this->out("Getting a list of course pages");
        $urls = $this->getListOfCoursePages();
        $urlsCount = count($urls);

        // Step 2: Go through the page and create/update offering
        $this->out("Number of courses found: $urlsCount");
        $this->out("Gathering details about each course");

        $courseDetails = array();
        foreach($urls as $url)
        {
            if(!$url) continue;
            $courseDetail = array();
            $this->domParser->load(file_get_contents(self::BASE_URL.$url));
            // Ignore self paced
            if(!$this->domParser->find('h2.offering_dates_date', 0)) {
                continue;
            }

            // Get Name and shortName
            $nameString = $this->domParser->find('h1.page-title', 0)->plaintext;
            $openBracketPosition = strpos($nameString,'(');
            $closeBracketPosition = strpos($nameString,')');
            $courseDetail['name'] = substr($nameString, 0, $openBracketPosition - 1);
            $courseDetail['shortName'] =substr($nameString,$openBracketPosition + 1,$closeBracketPosition - $openBracketPosition - 1) ;

            if($courseDetail['name'] == 'Introduction to Nursing in Healthcar')
            {
                $courseDetail['name'] = 'Introduction to Nursing in Healthcare';
                $courseDetail['shortName'] = 'IntroNur';
            }
            // Get the video id from the url
            // eg. www.youtube.com/embed/Bw8HkjGQb3U?wmode=opaque&rel=0&showinfo=0
            $youtubeIdPosition = 31;
            $video = 'http://' . $this->domParser->find('iframe.media-youtube-player',0)->src;
            $questionMarkPosition = strpos($video,'?');
            $courseDetail['video'] = 'http://www.youtube.com/watch?v=' . substr($video, $youtubeIdPosition, $questionMarkPosition - $youtubeIdPosition);

            $instructors = trim($this->domParser->find('div[id=subject-teacher-tagline]',0)->plaintext);
            // Remove the 'by'
            $instructors = substr($instructors,3);
            $courseDetail['instructors'] = explode(' & ', $instructors);


            $courseDetail['desc'] = $this->domParser->find('div.offering_body',0)->plaintext;
            $courseDetail['start_date'] = $this->domParser->find('h2.offering_dates_date', 0)->plaintext;
            $courseDetail['end_date'] = $this->domParser->find('h2.offering_dates_date', 1)->plaintext;
            $courseDetail['url'] = $url;

            print_r($courseDetail);

            $courseDetails[] = $courseDetail;
            $this->domParser->clear();
        }

        $this->out(count($courseDetails) . ' course pages found');
        // Default stream
        $stream = $this->dbHelper->getStreamBySlug('business');
        $this->out("Default stream is " . $stream->getName());
        foreach($courseDetails as $courseDetail)
        {


            // Build a course object
            $course = new Course();
            $courseShortName = 'open2study_' . $courseDetail['shortName'];
            $course->setShortName($courseShortName);
            $course->setInitiative($this->initiative);
            $course->setName($courseDetail['name']);
            $course->setDescription($courseDetail['desc']);
            $course->setStream($stream); // Default to Business
            $course->setVideoIntro($courseDetail['video']);
            $course->setUrl(self::BASE_URL . $courseDetail['url']);

            $dbCourse = $this->dbHelper->getCourseByShortName($courseShortName);
            if(!$dbCourse)
            {
                if($this->doCreate())
                {
                    // New course
                    $this->out("NEW COURSE - " . $course->getName());
                    if ($this->doModify())
                    {
                        foreach($courseDetail['instructors'] as $instructor)
                        {
                            $course->addInstructor($this->dbHelper->createInstructorIfNotExists($instructor));
                        }

                        $em->persist($course);
                        $em->flush();
                    }
                }
            } else {
                $course = $dbCourse;
            }


            // Check if offering exists
            $shortName = $this->getOfferingShortName($courseDetail);
            $offering = $this->dbHelper->getOfferingByShortName($shortName);
            if($offering)
            {
                continue;
            }

            // Check if create offering is oon
            if(!$this->doCreate())
            {
                $offerings[] = $offering; // Add it to the offerings table
                continue;
            }

            */

            $courses = $em->getRepository('ClassCentralSiteBundle:Course')->findBy(
                array('initiative'=>$this->getInitiative())
            );

            foreach($courses as $course)
            {
                if(in_array($course->getId(),self::$SELF_PACED_COURSES))
                {
                    continue;
                }

                $this->out($course->getName());
                $startDate = new \DateTime(self::$NEXT_SESSION_START_DATE);
                $endDate = new \DateTime(self::$NEXT_SESSION_START_DATE);
                $endDate->add(new \DateInterval('P30D'));

                $offering = new Offering();
                $offering->setCourse($course);
                $offering->setStartDate($startDate);
                $offering->setEndDate($endDate);
                $offering->setStatus(Offering::START_DATES_KNOWN);
                $offering->setLength(4);
                $offering->setShortName( $this->getOfferingShortName($course->getId(),self::$NEXT_SESSION_START_DATE));
                $this->out( $course->getUrl() );
                $offering->setUrl($course->getUrl());
                if ($this->doModify()) {
                    try {
                        $em->persist($offering);
                        $em->flush();
                    } catch (\Exception $e) {
                        $this->out("OFFERING creation FAILED");
                    }


                }

                $offerings[] = $offering;
            }

        return $offerings;
    }

    /**
     * Gets the short name which is a unique key to identify the course
     * @param $courseDetail
     */
    private function getOfferingShortName($courseId, $startDate)
    {
        return strtolower($this->initiative->getCode() . '_' . $courseId. '_'.str_replace('-','_', $startDate));
    }

    /**
     * Goes to the course page and gets a list of course urls
     */
    private function getListOfCoursePages()
    {
        $urls = array();
        $this->domParser->load_file(self::COURSE_CATALOGUE);
        foreach($this->domParser->find('span.field-content') as $course)
        {
            $urls[] = $course->find('a',0)->href;
        }
        $this->domParser->clear();
        return $urls;
    }

    /**
     * Visits the course page and then creates an offering
     */
    private function getOfferingFromUrl($url)
    {
        $this->domParser->load_file($url);

        $this->domParser->clear();
    }
}