<?php

namespace ClassCentral\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use ClassCentral\SiteBundle\Entity\Offering;

class OfferingRepository extends EntityRepository {

    /**
     * Returns courses categorized as recent, ongoing, past and upcoming.
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
            if (count($initiative) > 1 ) {
                    // Others. Show courses with no initiative ids
                  $query->add('where', "$where AND (c.initiative IN ($initiativeIds) OR c.initiative is NULL)");
            }  else {
                $query->add('where', "$where AND c.initiative IN ($initiativeIds)");
            }            
        } else {
            $query->add('where', $where);
        }

        $query->add('select', 'o')
                ->add('from', 'ClassCentralSiteBundle:Offering o')
                ->join("o.course", "c")
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
    
    public function findAllByCourseIds($courseIds = array()) {
        $em = $this->getEntityManager();
        $str = implode(',', $courseIds);
        $offerings = $em->createQuery(
                        "   SELECT o FROM ClassCentralSiteBundle:Offering o JOIN  
                         o.course c WHERE o.status != :status  AND c.id IN ($str)"   )
                    ->setParameter('status', Offering::COURSE_NA)                    
                    ->getResult();
        
        return $this->categorizeOfferings($offerings);
    }
               

    /**
     * Returns courses categorized as recent, ongoing, past and upcoming.
     */
    private function categorizeOfferings($offerings) {
        
        // Initial setup
        $ongoing = array();
        $past = array();
        $upcoming = array();
        $recent = array();
        $recentlyAdded = array();
        $selfpaced = array();

        $now = new \DateTime;
        $twoWeeksAgo = new \DateTime();
        $twoWeeksAgo->sub(new \DateInterval('P14D'));
        $twoWeeksLater = new \DateTime();
        $twoWeeksLater->add(new \DateInterval('P14D'));
        
        // Iterate through the offerings and  categorize each one as upcoming, ongoing or past
        $offeringIds = array(); // Use this to get instructors

        // Hack to keep track of finished and unfinished courses to avoid showing duplicate results
        // TODO: Handle showing of finished courses correctly. Still has some bugs
        $notFinishedCourses = array();
        $finishedCourses = array();
        foreach ($offerings as $offering) {
            $startDate = $offering->getStartDate();
            $endDate = $offering->getEndDate();
            
            $offeringArray = $this->getOfferingArray($offering);
            $offeringIds[] = $offeringArray['id'];
            //$courseKey = str_replace(' ', '_',$offering->getCourse()->getName()) . '_';
            if($initiative = $offering->getInitiative()) {
                ///$courseKey .= $initiative->getName();
            }
            $courseKey = $offering->getCourse()->getId();;
            
            // Check if its recent
            if (($offering->getStatus() == Offering::START_DATES_KNOWN ) && $startDate >= $twoWeeksAgo && $startDate <= $twoWeeksLater) {
                $recent[] = $offeringArray;
            }
                        

            // Check if its recently added
            if (($offering->getStatus() != Offering::COURSE_NA ) && $offering->getCreated() >= $twoWeeksAgo) {
                $recentlyAdded[] = $offeringArray;
            }
            
            // Check if its self paced
            if($offering->getStatus() == Offering::COURSE_OPEN) {
                $selfpaced[] = $offeringArray;
                //$notFinishedCourses[$courseKey] = 1;
                continue;
            }
            
            // Check if its upcoming
            if ($startDate > $now) {
                $upcoming[] = $offeringArray;
                //$notFinishedCourses[$courseKey] = 1;
                continue;
            }

            // Check if its in the past. Also don't show these courses if either of these 2 things happen
            // 1. A previous offering of this course has already been shown in finished courses
            // 2. A new offering of this is either running or scheduled sometime in the future
            if ($endDate != null && $endDate < $now && $offering->getStatus() != Offering::COURSE_OPEN) {
                if(!isset($notFinishedCourses[$courseKey]) && !isset($finishedCourses[$courseKey])) {
                    $past[] = $offeringArray;
                    //$finishedCourses[$courseKey] = 1;
                }
                continue;
            }

            // Check if it belongs to ongoing
            if (($offering->getStatus() == Offering::START_DATES_KNOWN) || ($offering->getStatus() == Offering::COURSE_OPEN)) {
                $ongoing[] = $offeringArray;
                //$notFinishedCourses[$courseKey] = 1;
            }

            // ERROR: Should not come here
        }

        /*
        // Get all the instructors
        $instructors = $this->getEntityManager()->getRepository('ClassCentralSiteBundle:Instructor')->getInstructorsByOffering($offeringIds);        
       
        $types = array_keys(Offering::$types);    
        foreach ($types as $type)
        {            
            foreach ($$type as &$offering)
            {               
                if(isset($instructors[$offering['id']])) {
                    $offering['instructors'] = $instructors[$offering['id']];
                }
            }
        }
        */
        
        return compact("ongoing", "past", "upcoming", "recent", "recentlyAdded", "selfpaced");
    }
    
    /**
     * Returns an array of values for a particular display
     * @param \ClassCentral\SiteBundle\Entity\Offering $offering
     * @return Array
     */
    private function getOfferingArray(Offering $offering) {
        $offeringArray = array();
        $course = $offering->getCourse();

        $offeringArray['id'] = $offering->getId();
        $offeringArray['name'] = $offering->getName();
        $offeringArray['url'] = $offering->getUrl();
        $offeringArray['videoIntro'] = $course->getVideoIntro();
        $offeringArray['length'] = $course->getLength();
        $offeringArray['startTimeStamp'] = $offering->getStartTimestamp();
        $offeringArray['displayDate'] = $offering->getDisplayDate();
        $offeringArray['length'] = $course->getLength();
        $offeringArray['microdataDate'] = $offering->getMicrodataDate();
        $offeringArray['status'] = $offering->getStatus();
        
        // Stream
        $stream = $course->getStream();
        $offeringArray['stream']['name'] = $stream->getName();
        $offeringArray['stream']['slug'] = $stream->getSlug();
        $offeringArray['stream']['showInNav'] = $stream->getShowInNav();

        $initiative = $course->getInitiative();
        $offeringArray['initiative']['name'] = '';
        if ($initiative != null) {
            $offeringArray['initiative']['name'] = $initiative->getName();
            $offeringArray['initiative']['url'] = $initiative->getUrl();
            $offeringArray['initiative']['tooltip'] = $initiative->getTooltip();
        }
        
        // Add Institutions
        $offeringArray['institutions'] = array();
        foreach($course->getInstitutions() as $institution) {
            $offeringArray['institutions'][] = array(
                'name' => $institution->getName(),
                'url' => $institution->getUrl(),
                'slug' => $institution->getSlug(),
                'isUniversity' => $institution->getIsUniversity(),
            );
        }
        
        $offeringArray['instructors'] = array();
        foreach($course->getInstructors() as $instructor)
        {
            $offeringArray['instructors'][] = $instructor->getName();
        }

        // Generate the course url
        $offeringArray['courseSlug'] = $course->getSlug();
        $offeringArray['courseId'] = $course->getId();

        return $offeringArray;
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

