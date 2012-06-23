<?php

namespace ClassCentral\SiteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use ClassCentral\SiteBundle\Entity\Offering;

class OfferingRepository extends EntityRepository{
    
    /**
     * Returns courses categoried as recent, ongoing, past and upcoming.
     * If no initiative is provided then its retunrs all the courses
     *
     */
    public function findAllByInitiative( $initiative = null ){
        // Initial setup
        $ongoing = array();
        $past = array();
        $upcoming = array();
        $recent = array();

        $now = new \DateTime;
        $twoWeeksAgo = new \DateTime(); 
        $twoWeeksAgo->sub( new \DateInterval('P14D') );
        $twoWeeksLater = new \DateTime();
        $twoWeeksLater->add( new \DateInterval('P14D') );

        $em = $this->getEntityManager();

        $query = $em->createQueryBuilder();
        

        $where = 'o.status != :status';
        if( $initiative != null )
        {
            $query->add('where', "$where AND o.initiative = :initiative" )->setParameter( 'initiative', $initiative);
        }
        else
        {
            $query->add('where', $where );
        }

        $query->add('select', 'o')
            ->add('from', 'ClassCentralSiteBundle:Offering o')
            ->add('orderBy','o.startDate ASC')
            ->setParameter('status',Offering::COURSE_NA);
        $allOfferings = $query->getQuery()->getResult(); 

        // Iterate through the offerings and  categorize each one as upcoming, ongoing or past
        foreach( $allOfferings as $offering )            
        {
            $startDate = $offering->getStartDate();
            $endDate = $offering->getEndDate();
                       

            // Check if its recent
            if ( ($offering->getStatus() == Offering::START_DATES_KNOWN ) && $startDate >= $twoWeeksAgo && $startDate <= $twoWeeksLater)
            {
                $recent[] = $offering;
            }

            // Check if its upcoming
            if( $startDate > $now )
            {
                $upcoming[] = $offering;
                continue;
            }

            // Check if its in the past
            if ( $endDate != null && $endDate < $now && $offering->getStatus() != Offering::COURSE_OPEN )
            {
                $past[] = $offering;
                continue;
            }


            // Check if it belongs to ongoing
            if( ($offering->getStatus() == Offering::START_DATES_KNOWN) || ($offering->getStatus() == Offering::COURSE_OPEN) )
            {
                $ongoing[] = $offering;
            }

            // ERROR: Should not come here

        }

         return compact( "ongoing","past","upcoming", "recent");
        
    }
    
    /**
     * Builds a list of courses starting or finishing in the current month
     *
     */
    public function findAllInCurrentMonth()
    {
        $starting = array();
        $finishing = array();
        $now = new \DateTime;
        $currentMonth = $now->format('m');
        $em = $this->getEntityManager();

        $query = $em->createQueryBuilder();
        
        $query->add('select', 'o')
            ->add('from', 'ClassCentralSiteBundle:Offering o')
            ->add('orderBy','o.startDate ASC')
            ->add('where', 'o.status != :status' )
            ->setParameter('status',Offering::COURSE_NA);

        $allOfferings = $query->getQuery()->getResult(); 

        foreach ( $allOfferings as $offering )
        {
            if ( $offering->getStartDate()->format('m') == $currentMonth )
            {
                $starting[] = $offering;
            }
         
            if ( $offering->getEndDate() != null && $offering->getEndDate()->format('m') == $currentMonth )
            {
                $ending[] = $offering;
            }
        }

        return compact( 'starting', 'ending');
        
    }
}

