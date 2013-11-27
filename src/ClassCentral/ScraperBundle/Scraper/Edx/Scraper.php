<?php

namespace ClassCentral\ScraperBundle\Scraper\Edx;

use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Offering;

class Scraper extends ScraperAbstractInterface
{
    const BASE_URL = "https://www.edx.org";
    const COURSE_CATALOGUE = "https://www.edx.org/course-list/allschools/allsubjects/allcourses";

    public function scrape()
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

            if($offering->getStatus() == Offering::START_MONTH_KNOWN && $startDate != $offering->getStartDate()->format("F Y"))
            {
                $this->out("INCORRECT START MONTH");
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
}