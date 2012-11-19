<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Offering;

class SearchController extends Controller{
       
    public function indexAction() {
        $request = $this->getRequest();
        $keywords = $request->get('q');
        $offerings = null;
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
            }                       
        }
                

        return $this->render('ClassCentralSiteBundle:Search:index.html.twig', array(
            'offerings' => $offerings, 
            'page' => 'search', 
            'total' => $total,
            'keywords' => $keywords,
            'offeringTypes' => Offering::$types,            
        ));        
    }
}