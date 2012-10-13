<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    
    private $offeringTypes = array(
        'recent' => array('desc' => 'Recently started or starting soon','nav'=>'Recently started or starting soon'),
        'recentlyAdded' => array('desc' => 'Just Announced','nav'=>'Just Announced'),
        'ongoing' => array('desc' => 'Courses in Progess', 'nav'=>'Courses in Progess'),
        'upcoming' => array('desc' => 'Future courses', 'nav'=>'Future courses'),
        'past' => array('desc' => 'Finished courses', 'nav'=>'Finished courses')
    );

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

        $offerings = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByInitiative();

        return $this->render('ClassCentralSiteBundle:Default:index.html.twig', 
                            array( 'offerings' => $offerings, 'page' => 'home', 'offeringTypes'=> $this->offeringTypes ));
    }
    
    public function coursesAction($type = 'upcoming'){
        if(!in_array($type, array_keys($this->offeringTypes))){
            // TODO: render an error page
            return false;
        }
        
            
        $offerings = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByInitiative();
        return $this->render('ClassCentralSiteBundle:Default:courses.html.twig', 
                array(
                    'offeringType' => $type,
                    'offerings' => $offerings,
                    'page'=>'courses',
                    'offeringTypes'=> $this->offeringTypes
                ));
    }

    public function faqAction() {
        return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array('page' => 'faq'));
    }
    
}
