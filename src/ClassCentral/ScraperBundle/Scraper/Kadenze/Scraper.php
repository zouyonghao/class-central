<?php

namespace ClassCentral\ScraperBundle\Scraper\Kadenze;

class Scraper extends \ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface
{

    const COURSES_API_ENDPOINT = 'https://www.kadenze.com/catalog.json?source=classcentral';

    private $courseFields = array(
        'Url', 'Description', 'DurationMin','DurationMax','Name','LongDescription','Certificate','WorkloadType','CertificatePrice'
    );

    private $offeringFields = array(
        'StartDate', 'EndDate', 'Url', 'Status'
    );

    public function scrape()
    {
        $em = $this->getManager();
        $kCourses = json_decode(file_get_contents( self::COURSES_API_ENDPOINT ), true );
        $coursesChanged = array();
        foreach($kCourses as $kcourse)
        {
            $this->out($kcourse['name']);
        }

    }

    public function getCourseEntity($course)
    {
        $c = new \ClassCentral\SiteBundle\Entity\Course();

    }
}