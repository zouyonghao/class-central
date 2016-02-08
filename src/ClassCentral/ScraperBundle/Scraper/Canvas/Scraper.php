<?php

namespace ClassCentral\ScraperBundle\Scraper\Canvas;

use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Services\Kuber;

class Scraper extends ScraperAbstractInterface
{

    const COURSE_CATALOG_URL = 'https://www.canvas.net/products.json?page=%s';

    private $courseFields = array(
        'Url', 'Description', 'Name', 'ShortName'
    );


    public function scrape()
    {


        $em = $this->getManager();
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
                //$this->output->writeLn( $canvasCourse['title'] );
                if( !$canvasCourse['free'] )
                {
                    // Skip paid courses.
                    continue;
                }

                $c = $this->getCourse( $canvasCourse );
                $dbCourse = null;
                $dbCourseFromSlug = $this->dbHelper->getCourseByShortName( $c->getShortName() );
                if( $dbCourseFromSlug  )
                {
                    $dbCourse = $dbCourseFromSlug;
                }
                else
                {
                    $dbCourseFromName = $this->dbHelper->findCourseByName($c->getName(), $this->initiative );
                    if($dbCourseFromName)
                    {
                        $dbCourse = $dbCourseFromName;
                    }
                }

                if( empty($dbCourse) )
                {
                    // New Course
                    $this->out("NEW COURSE - " . $c->getName());
                    // Create the course
                    if($this->doCreate())
                    {
                        // NEW COURSE
                        if ($this->doModify())
                        {
                            $em->persist($c);
                            $em->flush();

                            if( $canvasCourse['image'] )
                            {
                                $this->uploadImageIfNecessary( $canvasCourse['image'], $c);
                            }

                            // Send an update to Slack
                            $this->dbHelper->sendNewCourseToSlack( $c, $this->initiative );
                        }
                        $courseChanged = true;
                    }
                }
                else
                {
                    $changedFields = $this->dbHelper->changedFields($this->courseFields, $c,$dbCourse);
                    if( !empty($changedFields) && $this->doUpdate() )
                    {
                        $this->out("UPDATE COURSE - " . $dbCourse->getName() . " - ". $dbCourse->getId());
                        $this->dbHelper->outputChangedFields($changedFields);
                        if ($this->doModify())
                        {
                            $em->persist($dbCourse);
                            $em->flush();

                            if( $canvasCourse['image'] )
                            {
                                $this->uploadImageIfNecessary( $canvasCourse['image'], $dbCourse);
                            }
                        }
                        $courseChanged = true;
                    }
                }

            }

            $page++;
        }

        return $offerings;

    }

    public function getCourse($canvasCourse)
    {
        $dbLanguageMap = $this->dbHelper->getLanguageMap();

        $course = new Course();
        $course->setName( $canvasCourse['title'] );
        $course->setInitiative($this->initiative);
        $course->setDescription( $canvasCourse['teaser'] );
        $course->setUrl( $canvasCourse['url'] );
        $course->setLanguage( $dbLanguageMap['English']);
        $course->setStream(  $this->dbHelper->getStreamBySlug('cs') ); // Default to Computer Science
        $course->setShortName( 'canvas_' . $this->getSlug( $canvasCourse['path']) );

        return $course;
    }

    /**
     * Remove the session number from the path and returns the session slug.
     * i.e discover-your-value-10 will turn into discover-your-value
     * @param $path
     */
    private function getSlug( $path )
    {
        $sessionNumber = substr(strrchr($path,'-'),1);
        if ( !empty($sessionNumber) && is_numeric($sessionNumber) )
        {
            // slice the session number from the path
            return substr($path,0, strrpos($path,'-'));
        }

        return $path;
    }

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
}