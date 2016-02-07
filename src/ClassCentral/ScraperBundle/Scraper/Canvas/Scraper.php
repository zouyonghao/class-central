<?php

namespace ClassCentral\ScraperBundle\Scraper\Canvas;

use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;

class Scraper extends ScraperAbstractInterface
{

    const COURSE_CATALOG_URL = 'https://www.canvas.net/products.json?page=%s';

    public function scrape()
    {
        $defaultStream = $this->dbHelper->getStreamBySlug('cs');
        $dbLanguageMap = $this->dbHelper->getLanguageMap();
        $em = $this->getManager();
        $kuber = $this->container->get('kuber'); // File Api
        $offerings = array();

        $page = 1;


        while(true)
        {
            $coursesUrl = sprintf(self::COURSE_CATALOG_URL,$page);
            $courses = json_decode(file_get_contents($coursesUrl),true);
            if(empty($courses['products']))
            {
                // No more new courses
                break;
            }

            foreach($courses['products'] as $canvasCourse)
            {
                $this->output->writeLn( $canvasCourse['title'] );
            }

            $page++;
        }

        return $offerings;

    }
}