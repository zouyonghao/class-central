<?php

namespace ClassCentral\ScraperBundle\Scraper\Edx;

use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;

class Scraper extends ScraperAbstractInterface
{
    const BASE_URL = "https://www.edx.org/";
    const COURSE_CATALOGUE = "https://www.edx.org/course-list/allschools/allsubjects/allcourses";
    const COURSES_PER_PAGE = 7;

    public function scrape()
    {
        $numberOfCourses = $this->getNumberOfPages();
        $this->out("Number of courses calculated - " . $numberOfCourses);
        $numberOfPages = floor($numberOfCourses/self::COURSES_PER_PAGE);

        // Build a list of all course pages
        $coursePageUrls = array();
        for($page = 0; $page <= $numberOfPages; $page++)
        {
            $cataloguePage = file_get_html(self::COURSE_CATALOGUE . "?page=" . $page);
            foreach($cataloguePage->find('article.course-tile') as $courseDiv)
            {
                $coursePageUrls[] = $courseDiv->find('div.course-link',0)->href;
            }

        }

        $this->out("Number of courses found - " . count($coursePageUrls) + 1);

        $defaultStream = $this->dbHelper->getStreamBySlug('cs');
        foreach( $coursePageUrls as $coursePageUrl)
        {
            $url = self::BASE_URL . $coursePageUrl;
            $coursePage = file_get_html( $url );

            // Get the course code
            $courseShortName = $this->parseCourseCode($coursePageUrl);
            $courseName =  $coursePage->find('section.course-detail div.title', 0)->plaintext;
            $startDate = $coursePage->find("section.course-detail div.startdate",0)->plaintext;
            $startDate = $this->getStartDate($coursePage);
            $edXCourseId = $this->getEdxCourseId($coursePageUrl);
            $offering = $this->dbHelper->getOfferingByShortName("edx_" . $edXCourseId);
            if(!$offering)
            {
                $this->out("$courseName - $startDate");
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
        return substr($url, strrpos($url,'/')+1);
    }

    private function getStartDate($html)
    {
        $dateStr = $html->find("section.course-detail div.startdate",0)->plaintext;
        return substr($dateStr,strrpos($dateStr,':')+1);
    }

    /**
     * Calculates the number of pages of course catalogue
     */
    private function getNumberOfPages()
    {
        // Get the first page and then extract the total number of courses
        $this->domParser->load_file(self::COURSE_CATALOGUE);
        $countString = $this->domParser->find('div.counter',0)->plaintext;
        return (int)substr($countString,1,count($countString) - 2);
    }
}