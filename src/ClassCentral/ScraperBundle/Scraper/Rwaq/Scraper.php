<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/1/15
 * Time: 5:39 PM
 */

namespace ClassCentral\ScraperBundle\Scraper\Rwaq;


use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;


/**
 * Rwaq has a csv file. It assumed to be at extras/rwaq.csv
 * Class Scraper
 * @package ClassCentral\ScraperBundle\Scraper\Rwaq
 */
class Scraper extends ScraperAbstractInterface {

    const RWAQ_CSV_LOCATION =  'extras/rwaq.csv';

    public function scrape()
    {
        $handle = fopen(self::RWAQ_CSV_LOCATION, 'r');
        fgetcsv($handle); // Ignore the title line
        while (($data = fgetcsv($handle)) !== FALSE)
        {
            // Step 1: Check if the course exists
            $rwaqCourse = $this->getCourseEntity( $data );

            $this->out( $rwaqCourse->getName() . ' - ' . $rwaqCourse->getShortName() );
        }
    }

    /**
     * Build a doctrine Course Entity out of a csv row
     * @param $row
     * @return Course
     */
    public function getCourseEntity( $row )
    {
        $course = new Course();
        $course->setName( $row[0] );
        $course->setDescription( $row[1] );
        $course->setVideoIntro( str_replace('http','https',$row[4]) );
        $course->setUrl( $row[5] );
        $course->setShortName( $this->getCourseId( $row[5] ) );

        // Set the language to arabic
        $langMap = $this->dbHelper->getLanguageMap();
        $course->setLanguage( $langMap['Arabic']);

        // Set the default stream as humanities
        $defaultStream = $this->dbHelper->getStreamBySlug('humanities');
        $course->setStream( $defaultStream );

        return $course;
    }


    /**
     * Parses the url to create a unique id for the course
     * i.e http://www.rwaq.org/courses/introduction-to-dentistry-2
     * course id will be rwaq-introduction-to-dentistry
     * @param $url
     */
    public function getCourseId( $url )
    {
        $offeringId = $this->getOfferingId( $url );

        // Check if the offering id ends with a number. i.e -2,-3. If it does remove it and return the rest of the code
        $last = substr($offeringId, strrpos($offeringId,'-')+1);
        if( is_numeric( $last) )
        {
            return substr($offeringId, 0, strrpos($offeringId,'-'));
        }
        else
        {
            return $offeringId;
        }
    }

    /**
     * Parses the url to get unique id for the offering
     * i.e http://www.rwaq.org/courses/introduction-to-dentistry-2
     * offering id will be rwaq-introduction-to-dentistry-2
     * @param $url
     */
    public function getOfferingId( $url )
    {
        return 'rwaq-' . substr($url, strrpos($url,'/')+1);
    }


} 