<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 2/13/14
 * Time: 2:52 PM
 */

namespace ClassCentral\ScraperBundle\Scraper\Futurelearn;


use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Offering;


/**
 * The scraper leverages the api from kimonolabs
 * The scraper only checks for courses. Does not create or update them
 * Class Scraper
 * @package ClassCentral\ScraperBundle\Scraper\Futurelearn
 */
class Scraper extends ScraperAbstractInterface {

    const COURSES_API_ENDPOINT = 'http://www.kimonolabs.com/api/bjrzfecw';

    public function scrape()
    {
        $result = json_decode( $this->getCoursesJson(), true );

        foreach($result['results']['collection1'] as $course)
        {

            $courseName = $course['name']['text'];
            $url = $course['name']['href'];
            $startDate = $course['start_date'];
//            $this->out( $courseName .' - ' . $startDate);
//            continue;

            // Offering short name
            $osn = $this->getShortName($url);

            $offering = $this->dbHelper->getOfferingByShortName($osn);
            if(!$offering)
            {
                $this->out("NOT FOUND");
                $this->out("$courseName - $startDate");
                $this->out($url);
                $this->out("");
                continue;
            }

            try {
                if($offering->getStatus() == Offering::START_DATES_KNOWN)
                {
                    $offeringStartDate = new \DateTime($startDate);
                    if($offeringStartDate != $offering->getStartDate() )
                    {
                        $this->out("INCORRECT START DATE");
                        $this->out("$courseName - $startDate - Offering Id : {$offering->getId()}");
                        $this->out("Offering Date - {$offering->getDisplayDate()}");
                        $this->out("Course Page Start Date - $startDate");
                        $this->out($url);
                        $this->out("");
                    }
                    continue;


                }

                if($offering->getStatus() == Offering::START_MONTH_KNOWN && trim($startDate) != $offering->getStartDate()->format("F Y"))
                {
                    $this->out("INCORRECT START MONTH");
                    $this->out("$courseName - $startDate - Offering Id : {$offering->getId()}");
                    $this->out("Offering Date - {$offering->getDisplayDate()}");
                    $this->out($url);
                    $this->out("");
                    continue;
                }

                // Incorrect Date
                $this->out("INCORRECT START DATE");
                $this->out("$courseName - $startDate - Offering Id : {$offering->getId()}");
                $this->out("Offering Date - {$offering->getDisplayDate()}");
                $this->out("Course Page Start Date - $startDate");
                $this->out($url);
                $this->out("");

            } catch(\Exception $e) {
                $this->out("Error parsing dates");
                $this->out("$courseName - $startDate - Offering Id : {$offering->getId()}");
                $this->out("Offering Date - {$offering->getDisplayDate()}");
                $this->out($url);
                $this->out("");

            }
        }

        return array();
    }

    /**
     * Returns a json for all the Futurelearn courses
     */
    private function getCoursesJson()
    {
        $apiKey = $this->container->getParameter('kimono_api_key');
        $url = self::COURSES_API_ENDPOINT . '?apikey=' . $apiKey;


        return file_get_contents($url);
    }

    /**
     * Returns the offering offering
     */
    private function getShortName($url)
    {
        $pos = strrpos($url,'/');
        return 'fl-' . substr($url,$pos+1);
    }
} 