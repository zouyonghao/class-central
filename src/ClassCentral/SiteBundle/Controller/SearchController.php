<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\UserCourse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Offering;

class SearchController extends Controller{
       
    public function indexAction() {
        $filterService = $this->get('Filter');
        $request = $this->getRequest();
        $keywords = $request->get('q');
        $offerings = null;
        $subjects = null;
        $lang = null;
        $total = 0;
        if  (!empty($keywords)) {
            // Perform the search
            $search = $this->get('search');
            $search->setMatchMode(SPH_MATCH_BOOLEAN);            
            $results = $search->Query($keywords, $this->container->getParameter('sphinx_index'));
            $total= $results['total_found'];
            if($total != 0){
                 // Get all the document ids i.e offering ids
                $offeringIds = array();
                foreach ($results['matches'] as $match) {
                    $offeringIds[] = $match['id'];
                }
                $offerings = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByOfferingIds($offeringIds);
                $subjects = $filterService->getOfferingSubjects($offerings);
                $lang = $filterService->getOfferingLanguages($offerings);
            }                       
        }
                

        return $this->render('ClassCentralSiteBundle:Search:index.html.twig', array(
            'offerings' => $offerings, 
            'page' => 'search', 
            'total' => $total,
            'keywords' => $keywords,
            'offeringTypes' => Offering::$types,
            'offSubjects' => $subjects,
            'offLanguages' => $lang,
            'listTypes' => UserCourse::$lists,
        ));        
    }
}