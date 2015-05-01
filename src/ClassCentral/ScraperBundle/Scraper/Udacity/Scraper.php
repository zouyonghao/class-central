<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/30/15
 * Time: 7:08 PM
 */

namespace ClassCentral\ScraperBundle\Scraper\Udacity;


use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Services\Kuber;

class Scraper extends ScraperAbstractInterface{

    const COURSES_API_ENDPOINT = 'https://www.udacity.com/public-api/v0/courses';

    private $courseFields = array(
        'Url', 'Description', 'Length', 'Name','LongDescription','Certificate','VideoIntro', 'Syllabus',
        'WorkloadMin','WorkloadMax'
    );

    private $offeringFields = array(
       'Url'
    );

    public function scrape()
    {
        $em = $this->getManager();
        $udacityCourses = json_decode( file_get_contents(self::COURSES_API_ENDPOINT), true );
        $coursesChanged = array();

        foreach ($udacityCourses['courses'] as $udacityCourse)
        {
            $course = $this->getCourseEntity( $udacityCourse );
            $offering = null;
            $dbCourse = $this->dbHelper->getCourseByShortName( $course->getShortName() );
            if( !$dbCourse )
            {
                $dbCourse = $this->dbHelper->findCourseByName( $course->getName() , $this->initiative );
            }

            if( !$dbCourse )
            {

                // Course does not exist create it.
                if($this->doCreate())
                {
                    $this->out("NEW COURSE - " . $course->getName());

                    // NEW COURSE
                    if ($this->doModify())
                    {
                        $em->persist($course);
                        $em->flush();

                        if( $udacityCourse['banner_image'] )
                        {
                            $this->uploadImageIfNecessary($udacityCourse['banner_image'], $course);
                        }


                        // Create new offering
                        $offering = new Offering();
                        $offering->setCourse( $course );
                        $offering->setUrl( $course->getUrl() );

                        $startDate = new \DateTime();
                        $offering->setStartDate( $startDate );

                        $endDate = new \DateTime();
                        $endDate->add( new \DateInterval('P30D'));
                        $offering->setEndDate( $endDate );

                        $offering->setStatus( Offering::COURSE_OPEN );

                        $em->persist( $offering );
                        $em->flush();
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
                    //$this->out( "Database course changed " . $dbCourse->getName());
                    // Course has been modified
                    $this->out("UPDATE COURSE - " . $dbCourse->getName() . " - ". $dbCourse->getId());
                    $this->outputChangedFields($changedFields);
                    if ($this->doModify())
                    {
                        $em->persist($dbCourse);
                        $em->flush();

                        if( $udacityCourse['banner_image'] )
                        {
                            $this->uploadImageIfNecessary( $udacityCourse['banner_image'], $dbCourse);
                        }
                    }
                    $courseChanged = true;
                }

                // Check if offering has been modified
                $offering = $dbCourse->getNextOffering();
                if($offering->getUrl() != $course->getUrl() && $this->doUpdate() )
                {
                    $this->out("UPDATE COURSE - " . $dbCourse->getName() . " - ". $dbCourse->getId());
                    // Offering modified
                    $this->outputChangedFields( array( array(
                        'field' => 'offering Url',
                        'old' => $offering->getUrl(),
                        'new' => $course->getUrl()

                    ) ) );

                    if ($this->doModify())
                    {
                        $offering->setUrl( $course->getUrl() );
                        $em->persist( $offering );
                        $em->flush();
                    }
                    $courseChanged = true;
                }


                $course = $dbCourse;


            }
        }

        if( $courseChanged )
        {
            $coursesChanged[] = $course;
        }

        return $coursesChanged;
    }

    private function getCourseEntity( $udacityCourse = array() )
    {
        $defaultStream = $this->dbHelper->getStreamBySlug('cs');
        $langMap = $this->dbHelper->getLanguageMap();
        $defaultLanguage = $langMap[ 'English' ];

        $course = new Course();
        $course->setShortName( 'udacity_' . $udacityCourse['slug']);
        $course->setInitiative( $this->initiative );
        $course->setName( $udacityCourse['title'] );
        $course->setDescription( $udacityCourse['short_summary'] );
        $course->setLanguage( $defaultLanguage);
        $course->setStream($defaultStream); // Default to Computer Science
        $course->setCertificate( false );
        $course->setUrl( $udacityCourse['homepage'] );
        $course->setSyllabus( nl2br($udacityCourse['syllabus']) );
        $course->setWorkloadMin( 6 ) ;
        $course->setWorkloadMax( 6 ) ;
        ;

        // Calculate length
        $length = null;
        $expectedDuration = $udacityCourse['expected_duration'];
        if( $udacityCourse['expected_duration_unit'] == 'months')
        {
            $length = $expectedDuration * 4;
        }
        elseif ($udacityCourse['expected_duration_unit'] == 'weeks')
        {
            $length = $expectedDuration;
        }
        $course->setLength( $expectedDuration );

        // Calculate Description
        $course->setLongDescription( nl2br($udacityCourse['summary'] . '<br/><br/><b>Why Take This Course?</b><br/>' .  $udacityCourse['expected_learning']));

        // Intro Video
        if( !empty($udacityCourse['teaser_video']['youtube_url']) )
        {
            $course->setVideoIntro( $udacityCourse['teaser_video']['youtube_url'] );
        }


        return $course;
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

    private function outputChangedFields($changedFields)
    {
        foreach($changedFields as $changed)
        {
            $field = $changed['field'];
            $old = is_a($changed['old'], 'DateTime') ? $changed['old']->format('jS M, Y') : $changed['old'];
            $new = is_a($changed['new'], 'DateTime') ? $changed['new']->format('jS M, Y') : $changed['new'];

            $this->out("$field changed from - '$old' to '$new'");
        }
    }
}