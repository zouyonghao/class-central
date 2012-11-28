<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Offering;

/**
 * Renders the navigation bar
 */
class NavigationController extends Controller{
    
     private $offeringCountCacheKey = 'navigation_offerings_count';
     private $initiativeCountCacheKey ='navigation_initiatives_count';
         
    
    public function indexAction($page){
                        
        $cache = $this->get('cache');        
        $offeringCount = $cache->get($this->offeringCountCacheKey, array($this,'getOfferingCount'));
        $initiativeCount = $cache->get($this->initiativeCountCacheKey, array($this,'getInitiativeCount'));
        
        return $this->render('ClassCentralSiteBundle:Helpers:navbar.html.twig', 
                            array( 'offeringCount' => $offeringCount,'initiativeCount'=>$initiativeCount, 
                                   'page' => $page, 'offeringTypes'=> Offering::$types, 
                                    'initiativeTypes' => Initiative::$types
                                ));  
    }
    
    public function getInitiativeCount(){
        $results = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Initiative')->getOfferingCountByInitative();
        $initiativeCount = array();
        $othersCode = Initiative::$types['others'];
        $initiativeCount[$othersCode]['count'] = 0;
        $initiativeCount[$othersCode]['name'] = 'Others';
        $initiativeCodes = array_values(Initiative::$types);
        
        foreach ($results as $result){            
            $name = $result['name'];
            $code = $result['code'];
            $count = $result['total']; // accessing the count
            
            if(in_array($code, $initiativeCodes)) {
                $initiativeCount[$code]['count'] = $count;
                $initiativeCount[$code]['name'] = $name;
            } else{
               $initiativeCount[$othersCode]['count'] += $count; 
            }
            
        }
        
        return $initiativeCount;
    }

    public function getOfferingCount(){
        $offerings = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByInitiative();
        $offeringCount = array();
        foreach (array_keys(Offering::$types) as $type) {
            $offeringCount[$type] = isset($offerings[$type]) ? count($offerings[$type]) : 0;
        }
        
        return $offeringCount;
    }
    
    private function getFromCacheIfExists($key, $function){
        $cache = $this->get('cache');
        
        if($cache->contains($key))
        {
            return unserialize($cache->fetch($key));
        } 
        else 
        {
            $data = $this->$function();
            $cache->save($key, serialize($data), 3600);
            return $data;
        }
        
    }
}