<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Offering;

class DefaultController extends Controller {
               
    public function indexAction() {
  
        $cache = $this->get('Cache');
        $offerings = $cache->get('default_index_offerings',
                    array ($this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering'),'findAllByInitiative'));                

        return $this->render('ClassCentralSiteBundle:Default:index.html.twig', 
                            array( 'offerings' => $offerings, 'page' => 'home', 'offeringTypes'=> Offering::$types ));
    }
    
    
    
    public function coursesAction($type = 'upcoming'){
        if(!in_array($type, array_keys(Offering::$types))){
            // TODO: render an error page
            return false;
        }
        
            
        $cache = $this->get('Cache');
        $offerings = $cache->get('default_courses_offerings_' . $type,
                    array ($this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering'),'findAllByInitiative'));   
        
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
        
        $cache = $this->get('Cache');        
        $initiative = $cache->get('default_initative_ids_'. $type, array($this, 'getInitiativeIds'), array($type));
        $offerings = $cache->get('default_initiative_offerings_' . $type,
                    array ($this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering'),'findAllByInitiative'), array($initiative['ids']));  
                      
        return $this->render('ClassCentralSiteBundle:Default:initiative.html.twig', 
                array(
                    'initiative' => $initiative['name'],
                    'offerings' => $offerings,
                    'page'=>'initative',
                    'offeringTypes'=> Offering::$types
                ));
        
        
    }
    
    public function getInitiativeIds($type)
    {
        $initiativeTypes = Initiative::$types;
        
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
        
        return array('name' => $initiativeName, 'ids' => $initiativeIds);
    }

    public function faqAction() {
        return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array('page' => 'faq'));
    }
    
    /**
     * 
     * Cache cant be cleared from the command line. So creating an action
     */
    public function clearCacheAction(){
        $this->get('cache')->clear();
        // Just adding a dummy page
        return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array('page' => 'faq'));
    }
    
}
