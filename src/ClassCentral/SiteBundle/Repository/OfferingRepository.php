<?php

namespace ClassCentral\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use ClassCentral\SiteBundle\Entity\Offering;

class OfferingRepository extends EntityRepository {

    /**
     * Returns courses categoried as recent, ongoing, past and upcoming.
     * If no initiative is provided then its returns all the courses
     *
     */
    public function findAllByInitiative($initiative = null) {
   
        
        $em = $this->getEntityManager();
        $query = $em->createQueryBuilder();

        $where = 'o.status != :status';
        if ($initiative != null) {
            // initiative is an array of ids
            $initiativeIds = implode(',', $initiative);
            $query->add('where', "$where AND o.initiative IN($initiativeIds)");
        } else {
            $query->add('where', $where);
        }

        $query->add('select', 'o')
                ->add('from', 'ClassCentralSiteBundle:Offering o')
                ->add('orderBy', 'o.startDate ASC')
                ->setParameter('status', Offering::COURSE_NA);
        $allOfferings = $query->getQuery()->getResult();
        
        return $this->categorizeOfferings($allOfferings);
    }
    
    /**
     * Returns the courses by offeringId
     */
    public function findAllByOfferingIds($offeringIds = array()){
        $em = $this->getEntityManager();
        $query = $em->createQueryBuilder();
        $query->add('select', 'o')
               ->add('from', 'ClassCentralSiteBundle:Offering o')
               ->add('orderBy', 'o.startDate ASC')
                ->add('where','o.status != :status AND o.id IN ('. implode(',', $offeringIds) .')')
               ->setParameter('status', Offering::COURSE_NA)            
            ;
        $offerings = $query->getQuery()->getResult();
        
        return $this->categorizeOfferings($offerings);
    }
        

    /**
     * Returns courses categoried as recent, ongoing, past and upcoming.
     */
    private function categorizeOfferings($offerings) {
        
        // Initial setup
        $ongoing = array();
        $past = array();
        $upcoming = array();
        $recent = array();
        $recentlyAdded = array();

        $now = new \DateTime;
        $twoWeeksAgo = new \DateTime();
        $twoWeeksAgo->sub(new \DateInterval('P14D'));
        $twoWeeksLater = new \DateTime();
        $twoWeeksLater->add(new \DateInterval('P14D'));
        
        // Iterate through the offerings and  categorize each one as upcoming, ongoing or past
        foreach ($offerings as $offering) {
            $startDate = $offering->getStartDate();
            $endDate = $offering->getEndDate();


            // Check if its recent
            if (($offering->getStatus() == Offering::START_DATES_KNOWN ) && $startDate >= $twoWeeksAgo && $startDate <= $twoWeeksLater) {
                $recent[] = $offering;
            }

            // Check if its recntly added
            if (($offering->getStatus() != Offering::COURSE_NA ) && $offering->getCreated() >= $twoWeeksAgo) {
                $recentlyAdded[] = $offering;
            }
            // Check if its upcoming
            if ($startDate > $now) {
                $upcoming[] = $offering;
                continue;
            }

            // Check if its in the past
            if ($endDate != null && $endDate < $now && $offering->getStatus() != Offering::COURSE_OPEN) {
                $past[] = $offering;
                continue;
            }


            // Check if it belongs to ongoing
            if (($offering->getStatus() == Offering::START_DATES_KNOWN) || ($offering->getStatus() == Offering::COURSE_OPEN)) {
                $ongoing[] = $offering;
            }

            // ERROR: Should not come here
        }

        return compact("ongoing", "past", "upcoming", "recent", "recentlyAdded");
    }

    /**
     * Builds a list of courses starting or finishing in the current month
     *
     */
    public function findAllInCurrentMonth() {
        $starting = array();
        $finishing = array();
        $now = new \DateTime;
        $currentMonth = $now->format('m');
        $em = $this->getEntityManager();

        $query = $em->createQueryBuilder();

        $query->add('select', 'o')
                ->add('from', 'ClassCentralSiteBundle:Offering o')
                ->add('orderBy', 'o.startDate ASC')
                ->add('where', 'o.status != :status')
                ->setParameter('status', Offering::COURSE_NA);

        $allOfferings = $query->getQuery()->getResult();

        foreach ($allOfferings as $offering) {
            if ($offering->getStartDate()->format('m') == $currentMonth) {
                $starting[] = $offering;
            }

            if ($offering->getEndDate() != null && $offering->getEndDate()->format('m') == $currentMonth) {
                $ending[] = $offering;
            }
        }

        return compact('starting', 'ending');
    }

    /*
     * Generates a list of courses that can be registered for in a particular month
     *
     * @month Number between 0 - 12. Defaults to currenth month
     * @year Defaults to current year
     */

    public function courseReport($month = null, $year = null) {
        $dt = new \DateTime;
        if (!$month) {
            $month = $dt->format('m');
        }
        if (!$year) {
            $year = $dt->format('Y');
        }

        $allOfferings = $this->getAllCourses();

        // filter the courses
        $offerings = array();
        foreach ($allOfferings as $offering) {
            if ($offering->getStatus() == Offering::COURSE_OPEN) {
                $offerings[] = $offering;
                continue;
            }
            $startDate = $offering->getStartDate();
            if ($startDate->format('m') == $month && $startDate->format('Y') == $year) {
                $offerings[] = $offering;
            }
        }

        return $offerings;
    }

    public function getAllCourses() {
        $query = $this->getEntityManager()->createQueryBuilder();

        $query->add('select', 'o')
                ->add('from', 'ClassCentralSiteBundle:Offering o')
                ->add('orderBy', 'o.startDate ASC')
                ->add('where', 'o.status != :status')
                ->setParameter('status', Offering::COURSE_NA);

        return $query->getQuery()->getResult();
    }

}

