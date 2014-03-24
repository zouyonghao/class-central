<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/23/14
 * Time: 9:55 PM
 */

namespace ClassCentral\SiteBundle\Tests\Utility;


use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Utility\CourseUtility;

class CourseUtilityTest extends  \PHPUnit_Framework_TestCase {


    public function testCalculateState()
    {
        $now = new \DateTime();
        $future = new \DateTime();
        $future->add(new \DateInterval('P200D'));
        $past = new \DateTime();
        $past->sub(new \DateInterval('P200D'));
        $recent = new \DateTime();
        $recent->add(new \DateInterval('P7D'));


        $offering = new Offering();
        $offering->setCreated($now);
        $offering->setStartDate($future);
        $offering->setStatus(Offering::START_DATES_KNOWN);

        $state = CourseUtility::calculateStateWithDate($offering, $now);



        // Test recent + upcoming
        $offering = $this->getOffering($past, $recent, $future);
        $state = CourseUtility::calculateStateWithDate($offering, $now);
        $this->assertEquals(
            Offering::STATE_RECENT + Offering::STATE_UPCOMING,
            $state,
            "State is not recent and upcoming"
        );

        // Test just announced + upcoming
        $offering = $this->getOffering($now, $future, $future);
        $state = CourseUtility::calculateStateWithDate($offering, $now);
        $this->assertEquals(
            Offering::STATE_JUST_ANNOUNCED + Offering::STATE_UPCOMING,
            $state,
            "State is not just announced and upcoming"
        );

        // Test finished
        $offering = $this->getOffering($past, $past, $past);
        $state = CourseUtility::calculateStateWithDate($offering, $now);
        $this->assertEquals(
            Offering::STATE_FINISHED,
            $state,
            "State is not finished"
        );

        // Test on going
        $offering = $this->getOffering($past, $past, $future);
        $state = CourseUtility::calculateStateWithDate($offering, $now);
        $this->assertEquals(
            Offering::STATE_IN_PROGRESS,
            $state,
            "State is not in progress/ongoing"
        );

        // Test self paced
        $offering = $this->getOffering($past, $past, $past);
        $offering->setStatus(Offering::COURSE_OPEN);
        $state = CourseUtility::calculateStateWithDate($offering, $now);    
        $this->assertEquals(
            Offering::STATE_SELF_PACED,
            $state,
            "State is not self faced"
        );

    }

    private function getOffering($created, $starDate, $endDate)
    {
        $offering = new Offering();
        $offering->setCreated($created);
        $offering->setStartDate($starDate);
        $offering->setEndDate($endDate);
        $offering->setStatus(Offering::START_DATES_KNOWN);

        return $offering;
    }

} 