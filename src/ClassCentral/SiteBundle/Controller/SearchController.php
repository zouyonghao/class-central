<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SearchController extends Controller{
    
    private $offeringTypes = array(
        'recent' => array('desc' => 'Recently started or starting soon','nav'=>'Recently started or starting soon'),
        'recentlyAdded' => array('desc' => 'Just Announced','nav'=>'Just Announced'),
        'ongoing' => array('desc' => 'Courses in Progess', 'nav'=>'Courses in Progess'),
        'upcoming' => array('desc' => 'Future courses', 'nav'=>'Future courses'),
        'past' => array('desc' => 'Finished courses', 'nav'=>'Finished courses')
    );

    
    public function indexAction() {
        $request = $this->getRequest();
        $keywords = $request->get('q');
        $offerings = null;
        $total = 0;
        if  (!empty($keywords)) {
            // Perform the search
            $search = $this->get('search');
            $results = $search->Query($keywords, 'test1');
            $total= $results['total_found'];
            if($total != 0){
                 // Get all the document ids i.e offering ids
                $offeringIds = array();
                foreach ($results['matches'] as $match) {
                    $offeringIds[] = $match['id'];
                }
                $offerings = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByOfferingIds($offeringIds);
            }                       
        }
                

        return $this->render('ClassCentralSiteBundle:Search:index.html.twig', array(
            'offerings' => $offerings, 
            'page' => 'search', 
            'total' => $total,
            'keywords' => $keywords,
            'offeringTypes' => $this->offeringTypes,            
        ));        
    }
}