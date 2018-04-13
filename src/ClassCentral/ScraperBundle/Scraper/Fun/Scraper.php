<?php
/**
 * Created by PhpStorm.
 * User: inga
 * Date: 7/5/17
 * Time: 7:25 PM
 */

namespace ClassCentral\ScraperBundle\Scraper\Fun;


use ClassCentral\ScraperBundle\Scraper\ScraperAbstractInterface;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Offering;


class Scraper extends ScraperAbstractInterface
{


    const COURSES_API = 'https://www.fun-mooc.fr/fun/api/courses/?rpp=500';
    const FUN_COURSE_URL = 'https://www.fun-mooc.fr/courses/';

    private $courseFields = array(
        // Since the api lists multiple sessions with ids that are not unique it causes an update loop
        // at some point we may be able to update course info so im leaving it here.
    );


    private $offeringFields = array(
        'StartDate', 'EndDate', 'Url', 'Status',
    );


    public function scrape()
    {
        $em = $this->getManager();
        $funCourses = $this->getFUNJson();
        // pulls new API json or cached local version in tmp directory

        $coursesChanged = array();

        if (is_array($funCourses)) {

            foreach ($funCourses as $funCourse) {
                $courseChanged = false;
                $course = $this->getCourseEntity($funCourse);

                $dbCourse = $this->dbHelper->getCourseByShortName($course->getShortName());
                // check the DB for existing courses with same short name

                if (!$dbCourse) {
                    if ($this->doCreate()) {
                        $this->out('New Course: ' . $course->getName());
                        if ($this->doModify()) {
                            $em->persist($course);
                            $em->flush();
                            $this->dbHelper->sendNewCourseToSlack($course, $this->initiative);
                        }
                        $courseChanged = true;
                    }
                } else {
                    $courseModified = false;
                    $changedFields = array();
                    foreach ($this->courseFields as $field) {
                        $getter = 'get' . $field;
                        $setter = 'set' . $field;
                        if ($course->$getter() != $dbCourse->$getter()) {
                            $courseModified = true;

                            $changed = array();
                            $changed['field'] = $field;
                            $changed['old'] = $dbCourse->$getter();
                            $changed['new'] = $dbCourse->$getter();
                            $changedFields[] = $changed;

                            $dbCourse->$setter($course->$getter());
                        }
                    }
                    if ($courseModified && $this->doUpdate()) {

                        $this->out("UPDATE COURSE - " . $dbCourse->getName() . " - " . $dbCourse->getId());
                        $this->outputChangedFields($changedFields);
                        if ($this->doModify()) {
                            $em->persist($dbCourse);
                            $em->flush();
                        }
                        $courseChanged = true;
                    }
                    $course = $dbCourse;
                }

                $offering = $this->getOffering($funCourse, $course);

                $funOffering = ($course->getShortName() . '_' . $funCourse['session_number']);
                // example: fun_04014_5

                $dbOffering = $this->dbHelper->getOfferingByShortName($funOffering);

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
                    $offeringModified = false;
                    $changedFields = array();
                    foreach ($this->offeringFields as $field) {
                        $getter = 'get' . $field;
                        $setter = 'set' . $field;
                        if ($offering->$getter() != $dbOffering->$getter()) {
                            $offeringModified = true;
                            $changed = array();
                            $changed['field'] = $field;
                            $changed['old'] = $dbOffering->$getter();
                            $changed['new'] = $offering->$getter();
                            $changedFields[] = $changed;
                            $dbOffering->$setter($offering->$getter());

                        }
                    }
                    if ($offeringModified && $this->doUpdate()) {
                        $this->out('UPDATING OFFERING - ' . $dbOffering->getName());
                        $this->outputChangedFields($changedFields);
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
    }


    private function getCourseEntity($c = array())
    {

        $langMap = $this->dbHelper->getLanguageMap();
        $language = $langMap['French'];
        $stream = $this->dbHelper->getStreamBySlug('cs');
        $funFinalUrl = (self::FUN_COURSE_URL . $c['key'] . '/about');
        // example: https://www.fun-mooc.fr/courses/course-v1:MinesTelecom+04014+session05/about
        $key = $c['key'];
        $funTitle = $c['title'];

        $api_version = substr($c['key'], 0, 9);
        if ($api_version == 'course-v1') {
            $split_course_id = (explode("+session", $key));
            $split_course_id = $split_course_id[0];
            // split key so that only course-v1:MinesTelecom+04014 is returned

            $split_session = preg_split("/(S0)[1-9]+/", $split_course_id);
            $course_id = $split_session[0];

            if (isset($split_session[1]) and $split_session[1] != '') {
                // finds courses that are marked EN for english
                $course_id = $split_session[0] . $split_session[1];
                // append the EN back on so id is unique again
                // english (EN) and foreign courses share same course id in API
            }

            $shortName = preg_replace('/[^A-Za-z0-9\-]/', '_', $course_id); // Removes special chars.
            //$this->out($shortName);

        } else {
            $split_course_id = (explode("/session", $key));
            $split_course_id = $split_course_id[0];
            $shortName = preg_replace('/[^A-Za-z0-9\-]/', '_', $split_course_id); // Removes special chars.

            if (strpos($shortName, 'Trimestre') !== false) {

                $split_trimester = explode("_Trimestre", $shortName);
                $shortName = $split_trimester[0];
            }
        }

        if ($shortName == 'course-v1_Agreenium_66002') {
            $split_chemoocs = preg_split("/(\+session)[0-9][0-9]/", $key);
            if ($split_chemoocs[1] == 'A') {
                $shortName = 'course-v1_Agreenium_66002_advanced';
            }
            if ($split_chemoocs[1] == 'B') {
                $shortName = 'course-v1_Agreenium_66002_basic';
            }
        }

        $course = new Course();
        $course->setShortName($shortName);
        $course->setInitiative($this->initiative);
        $course->setIsMooc(true);
        $course->setName($funTitle);
        $course->setLanguage($language);
        $course->setStream($stream);
        $course->setUrl($funFinalUrl);
        $course->setCertificate(true);
        $course->setCertificatePrice(0);

        return $course;

    }

    private function outputChangedFields($changedFields)
    {
        foreach ($changedFields as $changed) {
            $field = $changed['field'];
            $old = is_a($changed['old'], 'DateTime') ? $changed['old']->format('jS M, Y') : $changed['old'];
            $new = is_a($changed['new'], 'DateTime') ? $changed['new']->format('jS M, Y') : $changed['new'];

            $this->out("$field changed from - '$old' to '$new'");
        }
    }

    private function getOffering($c, $course)
    {

        $funOfferingUrl = (self::FUN_COURSE_URL . $c['key'] . '/about');
        // example: https://www.fun-mooc.fr/courses/course-v1:MinesTelecom+04014+session05/about

        $offering = new Offering();
        $session = $c['session_number'];

        $offering->setShortName($course->getShortName() . '_' . $session);

        $row = array(
            $course->getShortName() . '_' . $session,
            $c['title']
        );

        $convert_start_date = date("Y-m-d", strtotime($c['start_date']));
        $convert_end_date = date("Y-m-d", strtotime($c['end_date']));

        $offering->setCourse($course);
        $offering->setUrl($funOfferingUrl);
        $offering->setStartDate(new \DateTime($convert_start_date));
        $offering->setEndDate(new \DateTime($convert_end_date));

        $offering->setStatus(Offering::START_DATES_KNOWN);
        return $offering;
    }


    private function getFUNJson()
    {
        $today = new \DateTime();
        $today = date_format($today, 'Y_m_d');

        $filename = "fun_$today.json";
        $filePath = '/tmp/' . $filename;

        $funCourses = array();

        if (file_exists($filePath)) {
            $getCourseFile = file_get_contents($filePath);
            $funCourses = json_decode($getCourseFile, true);
            $this->out("From Cached tmp File");
        } else {
            $this->out("PULLING FROM API");
            $json_request = file_get_contents(self::COURSES_API);

            $allCourses = json_decode($json_request, true);

            foreach ($allCourses['results'] as $funCourse) {
                //$this->out($funCourse['title']);
                $funCourses[] = $funCourse;
            }
            file_put_contents($filePath, json_encode($funCourses));
        }

        return $funCourses;

    }
}