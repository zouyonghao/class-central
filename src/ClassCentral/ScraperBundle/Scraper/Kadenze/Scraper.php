<?php

namespace ClassCentral\ScraperBundle\Scraper\Kadenze;

use ClassCentral\SiteBundle\Entity\Course;

class Scraper extends \ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface
{

    const COURSES_API_ENDPOINT = 'https://www.kadenze.com/catalog.json?source=classcentral';

    private $courseFields = array(
        'Url', 'Description','Name', 'ShortName','Description','LongDescription','Certificate','CertificatePrice'
    );

    private $offeringFields = array(
        'StartDate', 'EndDate', 'Url', 'Status'
    );

    public function scrape()
    {
        $em = $this->getManager();
        $kCourses = json_decode(file_get_contents( self::COURSES_API_ENDPOINT ), true );
        $coursesChanged = array();
        $courseService = $this->container->get('course');

        foreach($kCourses as $kcourse)
        {
            $course =  $this->getCourseEntity( $kcourse );
            $dbCourse = $this->dbHelper->getCourseByShortName( $course->getShortName() );

            if(!$dbCourse)
            {
                $dbCourse = $this->dbHelper->findCourseByName( $course->getName(), $this->initiative);
            }

            if(empty($dbCourse))
            {
                if($this->doCreate())
                {
                    $this->out("NEW COURSE - " . $course->getName());

                    // NEW COURSE
                    if ($this->doModify())
                    {
                        // Add instructors
                        foreach( $kcourse['instructors'] as $staff )
                        {
                            $insName = $staff['full_name'];
                            if(!empty($insName))
                            {
                                $course->addInstructor($this->dbHelper->createInstructorIfNotExists($insName));
                            }
                        }

                        $em->persist($course);
                        $em->flush();

                        $this->dbHelper->sendNewCourseToSlack( $course, $this->initiative );

                        if( $kcourse['logo'] )
                        {
                            $courseService->uploadImageIfNecessary( $kcourse['logo'], $course);
                        }
                    }
                    $courseChanged = true;

                }
            }
            else
            {
                // Check if any fields are modified
                $courseModified = false;
                $changedFields = array(); // To keep track of fields that have changed
                foreach($this->courseFields as $field)
                {
                    $getter = 'get' . $field;
                    $setter = 'set' . $field;
                    if($course->$getter() != $dbCourse->$getter())
                    {
                        $courseModified = true;

                        // Add the changed field to the changedFields array
                        $changed = array();
                        $changed['field'] = $field;
                        $changed['old'] =$dbCourse->$getter();
                        $changed['new'] = $course->$getter();
                        $changedFields[] = $changed;

                        $dbCourse->$setter($course->$getter());
                    }

                }

                if($courseModified && $this->doUpdate())
                {

                    // Course has been modified
                    $this->out("UPDATE COURSE - " . $dbCourse->getName() . " - ". $dbCourse->getId());
                    $this->dbHelper->outputChangedFields($changedFields);
                    if ($this->doModify())
                    {
                        $em->persist($dbCourse);
                        $em->flush();

                        if( $kcourse['logo'] )
                        {
                            $courseService->uploadImageIfNecessary( $kcourse['logo'], $dbCourse);
                        }
                    }
                    $courseChanged = true;
                }

                $course = $dbCourse;
            }
        }

    }

    public function getCourseEntity($course)
    {
        $defaultStream = $this->dbHelper->getStreamBySlug('art-and-design');
        $langMap = $this->dbHelper->getLanguageMap();
        $defaultLanguage = $langMap[ 'English' ];

        $c = new \ClassCentral\SiteBundle\Entity\Course();
        $c->setName($course['name']);
        $c->setUrl($course['url']);
        $c->setInitiative( $this->initiative );
        $c->setShortName( 'kadenze_'. $course['id'] );
        $c->setVideoIntro( $course['promo_video']);
        $c->setLanguage( $defaultLanguage);
        $c->setStream($defaultStream); // Default to Art and Design

        $c->setDescription( $course['description'] );
        $c->setLongDescription( $course['description'] );
        $c->setCertificate(true);
        $c->setCertificatePrice(Course::PAID_CERTIFICATE);

        return $c;
    }
}