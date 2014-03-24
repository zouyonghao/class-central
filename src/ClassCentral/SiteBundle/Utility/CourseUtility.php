<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 3/23/14
 * Time: 9:23 PM
 */

namespace ClassCentral\SiteBundle\Utility;


use ClassCentral\SiteBundle\Entity\Offering;

class CourseUtility {

    /**
     * Calculates the state of the offering - i.e recent, upcoming etc
     * @param Offering $offering
     */
    public static function calculateState(Offering $offering)
    {
        return self::calculateStateWithDate($offering, new \DateTime());
    }

    /**
     * By default the offering state is upcoming;
     * @param Offering $offering
     * @param \DateTime $now
     * @return int|string
     */
    public static function calculateStateWithDate(Offering $offering, \DateTime $now)
    {
        $state = 0;

        // Ignore offerings that are no longer available
        if($offering->getStatus() == Offering::COURSE_NA)
        {
            return $state;
        }

        $twoWeeksAgo = clone $now;
        $twoWeeksAgo->sub(new \DateInterval('P14D'));
        $twoWeeksLater = clone $now;
        $twoWeeksLater->add(new \DateInterval('P14D'));

        $startDate = $offering->getStartDate();
        $endDate = $offering->getEndDate();

        // Check if its recent
        if (($offering->getStatus() == Offering::START_DATES_KNOWN ) && $startDate >= $twoWeeksAgo && $startDate <= $twoWeeksLater) {
            $state += Offering::STATE_RECENT;
        }

        // Check if its recently added
        if ( $offering->getCreated() >= $twoWeeksAgo ) {
            $state += Offering::STATE_JUST_ANNOUNCED;
        }

        // Check if its self paced
        if($offering->getStatus() == Offering::COURSE_OPEN) {
            $state += Offering::STATE_SELF_PACED;
            return $state;
        }

        // Check if its finished
        if ($endDate != null && $endDate < $now) {
            $state += Offering::STATE_FINISHED;
            return $state;
        }

        // Check if its ongoing
        if ( $offering->getStatus() == Offering::START_DATES_KNOWN && $now >= $startDate) {
            $state += Offering::STATE_IN_PROGRESS;
            return $state;
        }

        // If it has reached here it means its upcoming.
        $state += Offering::STATE_UPCOMING;

        return $state;
    }
} 