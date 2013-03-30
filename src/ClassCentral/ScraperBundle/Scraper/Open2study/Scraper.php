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

        $this->output->writeln("Scraping " . $this->initiative->getName());

        // Step 1: Getting a list of course URLs
        $this->output->writeln("Getting a list of course pages");
        $urls = $this->getListOfCoursePages();

        // Step 2: Go through the page and create/update offering
        $this->output->writeln("Gather details about the course");

        $courseDetails = array();
        foreach($urls as $url)
        {
            $courseDetail = array();
            $this->domParser->load_file(self::BASE_URL.$url);

            // Get Name and shortName
            $nameString = $this->domParser->find('h1.page-title', 0)->plaintext;
            $openBracketPosition = strpos($nameString,'(');
            $closeBracketPosition = strpos($nameString,')');
            $courseDetail['name'] = substr($nameString, 0, $openBracketPosition - 1);
            $courseDetail['shortName'] =substr($nameString,$openBracketPosition + 1,$closeBracketPosition - $openBracketPosition - 1) ;

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

            // Step 1: Get the course
            $course = $this->dbHelper->createCourseIfNotExists($courseDetail['name'], $this->initiative, null, $stream);
            $courseId = $course->getId();
            // Check if course exists or was created successfully
            if($this->doModify() && empty($courseId))
            {
                $this->out("ERROR: COURSE {$courseDetail['name']} could not be created for initiative " . $this->initiative->getName());
                continue;
            }

            //Step 2: Get the instructors
            $instructors = array();
            foreach($courseDetail['instructors'] as $instructor)
            {
                $instructors[] = $this->dbHelper->createInstructorIfNotExists($instructor);
            }

            // Check if offering exists
            $shortName = $this->getOfferingShortName($courseDetail);
            $offering = $this->dbHelper->getOfferingByShortName($shortName);
            if($offering)
            {
                // TODO: Check if the offerings needs to be updated
                continue;
            }

            // Create the offering
            if(!$this->doCreate())
            {
                continue;
            }

            $offering = new Offering();
            $offering->setCourse($course);
            foreach($instructors as $instructor)
            {
                $offering->addInstructor($instructor);
            }
            $offering->setStartDate( \DateTime::createFromFormat("d/m/Y",$courseDetail['start_date']) );
            $offering->setEndDate( \DateTime::createFromFormat("d/m/Y",$courseDetail['end_date']) );
            $offering->setStatus(Offering::START_DATES_KNOWN);
            $offering->setLength(4);
            $offering->setShortName($shortName);
            $offering->setUrl(self::BASE_URL . $courseDetail['url']);
            $offering->setVideoIntro($courseDetail['video']);
            $offering->setSearchDesc($courseDetail['desc']);
            $offering->setCreated(new \DateTime());

            if($this->doCreate() && $this->doModify())
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