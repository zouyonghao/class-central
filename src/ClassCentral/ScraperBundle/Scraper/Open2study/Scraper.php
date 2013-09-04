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
    const COURSE_CATALOGUE = 'https://www.open2study.com/subjects';
    const BASE_URL = 'https://www.open2study.com/';

    public function scrape()
    {
        $em = $this->getEntityManager();
        // Array of offerings created or updated
        $offerings = array();

        $this->out("Scraping " . $this->initiative->getName());

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

            $courseDetail = array();
            $this->domParser->load_file(self::BASE_URL.$url);

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
            /**
             * Taking a shortcut here. Check if a course is created or not. If it isn't create the
             * course,offering, etc. Updates are ignored
             * TODO: Not take a shortcut
             */

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

            $offering = new Offering();
            $offering->setCourse($course);
            $offering->setStartDate( \DateTime::createFromFormat("d/m/Y",$courseDetail['start_date']) );
            $offering->setEndDate( \DateTime::createFromFormat("d/m/Y",$courseDetail['end_date']) );
            $offering->setStatus(Offering::START_DATES_KNOWN);
            $offering->setLength(4);
            $offering->setShortName($shortName);
            $offering->setUrl(self::BASE_URL . $courseDetail['url']);
            $offering->setVideoIntro($courseDetail['video']);
            $offering->setSearchDesc($courseDetail['desc']);
            $offering->setCreated(new \DateTime());

            if($this->doModify())
            {
                $em->persist($offering);
                $em->flush();
                $this->out("OFFERING {$courseDetail['name']} created");
            }

            $offerings[] = $offering;

        }

        return $offerings;
    }

    /**
     * Gets the short name which is a unique key to identify the course
     * @param $courseDetail
     */
    private function getOfferingShortName($courseDetail)
    {
        return strtolower($this->initiative->getCode() . '_' . $courseDetail['shortName']. '_'.str_replace('/','_', $courseDetail['start_date']));
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