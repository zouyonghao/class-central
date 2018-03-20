<?php

namespace ClassCentral\ScraperBundle\Scraper\Federica;

use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Offering;

class Scraper extends ScraperAbstractInterface
{

    const COURSES_FEED = 'http://www.federica.eu/api/feeds/f_class_central';

    private $courseFields = array(
        'Url', 'Description', 'Name', 'LongDescription', 'Language', 'DurationMin', 'DurationMax', 'Certificate'
    );

    private $offeringFields = array(
        'StartDate', 'EndDate', 'Url', 'Status'
    );

    public function scrape()
    {
        $em = $this->getManager();
        $courseService = $this->container->get('course');

        $fCourses = json_decode(file_get_contents(self::COURSES_FEED), true);

        $coursesChanged = array();

        foreach ($fCourses as $fCourse) {
            $courseChanged = false;
            $course = $this->getCourseEntity($fCourse);
            $dbCourse = $this->dbHelper->getCourseByShortName($course->getShortName());

            if (!$dbCourse) {
                // Course does not exist create it.
                if ($this->doCreate()) {
                    $this->out("NEW COURSE - " . $course->getName());

                    // NEW COURSE
                    if ($this->doModify()) {
                        $insName = $fCourse['educator'];
                        if (!empty($insName)) {
                            $course->addInstructor($this->dbHelper->createInstructorIfNotExists($insName));
                        }

                        $em->persist($course);
                        $em->flush();

                        $this->dbHelper->sendNewCourseToSlack($course, $this->initiative);

                        if ($fCourse['image_url']) {
                            $courseService->uploadImageIfNecessary($fCourse['image_url'], $course);
                        }

                        // Send an update to Slack

                    }
                    $courseChanged = true;

                }
            } else {
                // Check if any fields are modified
                $courseModified = false;
                $changedFields = array(); // To keep track of fields that have changed
                foreach ($this->courseFields as $field) {
                    $getter = 'get' . $field;
                    $setter = 'set' . $field;
                    if ($course->$getter() != $dbCourse->$getter()) {
                        $courseModified = true;

                        // Add the changed field to the changedFields array
                        $changed = array();
                        $changed['field'] = $field;
                        $changed['old'] = $dbCourse->$getter();
                        $changed['new'] = $course->$getter();
                        $changedFields[] = $changed;

                        $dbCourse->$setter($course->$getter());
                    }

                }

                if ($courseModified && $this->doUpdate()) {
                    // Course has been modified
                    $this->out("UPDATE COURSE - " . $dbCourse->getName() . " - " . $dbCourse->getId());
                    $this->dbHelper->outputChangedFields($changedFields);
                    if ($this->doModify()) {
                        $em->persist($dbCourse);
                        $em->flush();

                        if ($fCourse['image_url']) {
                            $courseService->uploadImageIfNecessary($fCourse['image_url'], $dbCourse);
                        }
                    }
                    $courseChanged = true;
                }

                $course = $dbCourse;
            }

            /***************************
             * CREATE OR UPDATE OFFERING
             ***************************/

            $offering = $this->getOfferingEntity($fCourse, $course);
            $dbOffering = $this->dbHelper->getOfferingByShortName($offering->getShortName());

            if (!$dbOffering) {
                if ($this->doCreate()) {
                    $this->out("NEW OFFERING - " . $offering->getName());
                    if ($this->doModify()) {
                        $em->persist($offering);
                        $em->flush();
                    }

                    $this->dbHelper->sendNewOfferingToSlack($offering);
                    $offerings[] = $offering;
                    $courseChanged = true;
                }
            } else {
                // old offering. Check if has been modified or not
                $offeringModified = false;
                $changedFields = array();
                foreach ($this->offeringFields as $field) {
                    $getter = 'get' . $field;
                    $setter = 'set' . $field;
                    if ($offering->$getter() != $dbOffering->$getter()) {
                        $offeringModified = true;
                        // Add the changed field to the changedFields array
                        $changed = array();
                        $changed['field'] = $field;
                        $changed['old'] = $dbOffering->$getter();
                        $changed['new'] = $offering->$getter();
                        $changedFields[] = $changed;
                        $dbOffering->$setter($offering->$getter());
                    }
                }

                if ($offeringModified && $this->doUpdate()) {
                    // Offering has been modified
                    $this->out("UPDATE OFFERING - " . $dbOffering->getName());
                    $this->dbHelper->outputChangedFields($changedFields);
                    if ($this->doModify()) {
                        $em->persist($dbOffering);
                        $em->flush();
                    }
                    $offerings[] = $dbOffering;
                    $courseChanged = true;
                }
            }

            if ($courseChanged) {
                $coursesChanged[] = $course;
            }
        }
    }

    private function getCourseEntity($c = array())
    {
        $defaultStream = $this->dbHelper->getStreamBySlug('cs');
        $langMap = $this->dbHelper->getLanguageMap();
        $defaultLanguage = $langMap['Italian'];
        if (!empty($c['language']) && $c['language'] == 'en') {
            $defaultLanguage = $langMap['English'];
        }

        $course = new Course();
        $course->setShortName('federica_' . $this->cleanName($c) . '-' . $c['uuid']);
        $course->setInitiative($this->initiative);
        $course->setName($c['name']);
        $course->setDescription($c['description']);
        $course->setLongDescription($c['description']);
        $course->setLanguage($defaultLanguage);
        $course->setStream($defaultStream); // Default to Computer Science
        $course->setUrl($c['url']);
        $course->setCertificate($c['has_certificates']);

        foreach ($c['runs'] as $runs) {
            if (isset($runs['duration_in_weeks'])) {
                $duration = $runs['duration_in_weeks'];
                $course->setDurationMin($duration);
                $course->setDurationMax($duration);
            }
        }

        $course->addInstitution($this->dbHelper->getInstitutionBySlug('university-of-naples'));

        return $course;
    }


    private function getOfferingEntity($fCourse, $course)
    {
        $offering = new Offering();
        $offering->setCourse($course);
        $offering->setUrl($course->getUrl());

        foreach ($fCourse['runs'] as $runs) {
            if (isset($runs['duration_in_weeks'])) {
                $duration = "+" . $runs['duration_in_weeks'] . " weeks";

            } else {
                $duration = "+4 weeks";
            }
            if (isset($runs['start_date'])) {
                $startDate = new \DateTime($runs['start_date']);

                // calculate end dates based off of duration from API
                $api_start_date = $runs['start_date'];
                $calc_end_date = strtotime($api_start_date);
                $calc_end_date = strtotime($duration, $calc_end_date);
                $calc_end_date = date('Y-m-d', $calc_end_date);
                $endDate = new \DateTime($calc_end_date);

                $offering->setStartDate($startDate);
                $offering->setEndDate($endDate);
                $offering->setStatus(Offering::START_DATES_KNOWN);
                $offering->setShortName('federica_'
                    . $this->cleanName($fCourse)
                    . '-'
                    . $fCourse['uuid']
                    . '_' . $runs['start_date']);

            } else {
                // Courses with no start date
                $offering->setStartDate(new \DateTime('2019-01-01'));
                $offering->setEndDate(new \DateTime('2019-01-06'));
                $offering->setStatus(Offering::START_DATES_UNKNOWN);
                $offering->setShortName('federica_' . $fCourse['uuid'] . '_' . '2019-01-01');

            }
        }
        return $offering;
    }

    private function cleanName($course_array)
    {
        $string = str_replace(' ', '-', $course_array['name']);
        // Replaces all spaces with hyphens.
        $name = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        // strips all special characters
        $cleaned_name = strtolower($name);
        // to lowercase

        return $cleaned_name;
    }
}