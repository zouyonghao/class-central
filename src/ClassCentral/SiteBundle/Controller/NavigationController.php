<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Renders the navigation bar
 */
class NavigationController extends Controller{
    
     private $cacheKey = 'cc_offerings_count';
    
     private $offeringTypes = array(
        'recent' => array('desc' => 'Recently started or starting soon','nav'=>'Recently started or starting soon'),
        'recentlyAdded' => array('desc' => 'Just Announced','nav'=>'Just Announced'),
        'ongoing' => array('desc' => 'Courses in Progess', 'nav'=>'Courses in Progess'),
        'upcoming' => array('desc' => 'Future courses', 'nav'=>'Future courses'),
        'past' => array('desc' => 'Finished courses', 'nav'=>'Finished courses')
    );
    
    public function indexAction($page){
        
        // Cache it to preserve space
        $cache = $this->get('cache');
        
        if($cache->contains($this->cacheKey))
        {
            $offeringCount = unserialize($cache->fetch($this->cacheKey));
        } 
        else 
        {            
            $offerings = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByInitiative();
            $offeringCount = array();
            foreach( array_keys($this->offeringTypes) as $type){
                $offeringCount[$type] = isset($offerings[$type]) ? count($offerings[$type]) : 0;
            }
            $cache->save($this->cacheKey,serialize($offeringCount),3600);
        }
        
        return $this->render('ClassCentralSiteBundle:Helpers:navbar.html.twig', 
                            array( 'offeringCount' => $offeringCount, 'page' => $page, 'offeringTypes'=> $this->offeringTypes ));  
    }
}