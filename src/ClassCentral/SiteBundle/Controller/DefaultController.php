<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Offering;

class DefaultController extends Controller {
        
    private $cacheKeyPrefix ='defaultController';
    
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
                            array( 'offerings' => $offerings, 'page' => 'home', 'offeringTypes'=> Offering::$types ));
    }
    
    public function coursesAction($type = 'upcoming'){
        if(!in_array($type, array_keys(Offering::$types))){
            // TODO: render an error page
            return false;
        }
        
            
        $offerings = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByInitiative();
        return $this->render('ClassCentralSiteBundle:Default:courses.html.twig', 
                array(
                    'offeringType' => $type,
                    'offerings' => $offerings,
                    'page'=>'courses',
                    'offeringTypes'=> Offering::$types
                ));
    }
    
    public function initiativeAction($type='coursera') {
        $initiativeTypes = Initiative::$types;
        
        if(!in_array($type, array_keys($initiativeTypes))){
            // TODO: render an error page
            return false;
        }
        
        // Get the initative id
        $initiativeIds = array();        
        if( $type != 'others'){
            $initiative = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Initiative')
                    ->findOneByCode($initiativeTypes[$type]);
            $initiativeName = $initiative->getName();
            $initiativeIds[] = $initiative->getId();
        } else {
            $initiativeName = 'Others';
            $em = $this->getDoctrine()->getEntityManager();
            $initiatives = implode("','", array_values($initiativeTypes));
            $query = $em->createQuery("SELECT i FROM ClassCentralSiteBundle:Initiative i WHERE i.code NOT IN ('$initiatives')");
            foreach($query->getResult() as $initiative){
                $initiativeIds[] = $initiative->getId();
            }
        }
        
              
        $offerings = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByInitiative($initiativeIds);
        return $this->render('ClassCentralSiteBundle:Default:initiative.html.twig', 
                array(
                    'initiative' => $initiativeName,
                    'offerings' => $offerings,
                    'page'=>'initative',
                    'offeringTypes'=> Offering::$types
                ));
        
        
    }

    public function faqAction() {
        return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array('page' => 'faq'));
    }
    
}
