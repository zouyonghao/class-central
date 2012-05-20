<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Entity\Offering;

class DefaultController extends Controller {

    public function indexAction() {

        // Not being shown currently
        /* 
        // Get some stats
        $stats['courses'] = $em->createQuery('SELECT COUNT(c.id) FROM ClassCentralSiteBundle:Course c')->getSingleScalarResult();
        $stats['instructors'] = $em->createQuery('SELECT COUNT(i.id) FROM ClassCentralSiteBundle:Instructor i')->getSingleScalarResult();

        // Get course counts by initiative
        $initiatives = $em->createQueryBuilder()->addSelect('ini.name, count(o) AS offerings')
                        ->from('ClassCentralSiteBundle:Initiative', 'ini')
                        ->leftjoin('ini.offerings', 'o')
                        ->where('o.startDate > :datetime')
                        ->addGroupBy('ini.id')
                        ->setParameter('datetime', $now->format("Y-m-d"))
                        ->getQuery()->getArrayResult();   
         */

        $offerings = $this->getOfferings();
        return $this->render('ClassCentralSiteBundle:Default:index.html.twig', 
                            array_merge( $offerings, array( 'page' => 'home' )));
    }

    public function faqAction() {
        return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array('page' => 'faq'));
    }
    
    /**
     * Returns an array of courses categorized as ongoing, upcoming and past
     */
    private function getOfferings(  $initiative = null ){
        // Initial setup
        $ongoing = array();
        $past = array();
        $upcoming = array();
        $now = new \DateTime;
        $em = $this->getDoctrine()->getEntityManager();

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
                       

            // Check if its upcoming
            if( $startDate > $now )
            {
                $upcoming[] = $offering;
                continue;
            }

            // Check if its in the past
            if ( $endDate != null && $endDate < $now )
            {
                $past[] = $offering;
                continue;
            }


            // Check if it belongs to ongoing
            if( $offering->getStatus() == Offering::START_DATES_KNOWN)
            {
                $ongoing[] = $offering;
            }

            // ERROR: Should not come here

        }


         return compact( "ongoing","past","upcoming");
       
    }

}
