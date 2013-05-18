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
     private $streamCountCacheKey = 'navigation_stream_count';
         
    
    public function indexAction($page)
    {
        $cache = $this->get('cache');        
        $offeringCount = $cache->get($this->offeringCountCacheKey, array($this,'getOfferingCount'));
        $initiativeCount = $cache->get($this->initiativeCountCacheKey, array($this,'getInitiativeCount'));
        $streamCount = $cache->get($this->streamCountCacheKey, array($this,'getStreamCount'));
        
        return $this->render('ClassCentralSiteBundle:Helpers:navbar.html.twig', 
                            array( 'offeringCount' => $offeringCount,'initiativeCount'=>$initiativeCount, 
                                   'page' => $page, 'offeringTypes'=> Offering::$types, 
                                    'initiativeTypes' => Initiative::$types, 'streams' => $streamCount
                                ));  
    }
    
    public function getInitiativeCount()
    {
        $results = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Initiative')->getCourseCountByInitative();
        $initiativeCount = array();
        $othersCode = Initiative::$types['others'];
        $initiativeCount[$othersCode]['count'] = 0;
        $initiativeCount[$othersCode]['name'] = 'Others';
        $initiativeCodes = array_values(Initiative::$types);
        
        foreach ($results as $result)
        {
            $name = $result['name'];
            $code = $result['code'];
            $count = $result['total']; // accessing the count
            
            if(in_array($code, $initiativeCodes))
            {
                $initiativeCount[$code]['count'] = $count;
                $initiativeCount[$code]['name'] = $name;
            } else
            {
               $initiativeCount[$othersCode]['count'] += $count; 
            }
            
        }
        
        return $initiativeCount;
    }

    public function getOfferingCount()
    {
        $offerings = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByInitiative();
        $offeringCount = array();
        foreach (array_keys(Offering::$types) as $type) {
            $offeringCount[$type] = isset($offerings[$type]) ? count($offerings[$type]) : 0;
        }
        
        return $offeringCount;
    }

    public function getStreamCount()
    {
        $streams = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Stream')->getCourseCountByStream();
        return $streams;
    }
    
}