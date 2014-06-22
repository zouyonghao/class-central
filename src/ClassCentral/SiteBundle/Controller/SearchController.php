<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\UserCourse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Offering;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller{
       
    public function indexAction(Request $request)
    {
        $cl = $this->get('course_listing');

        $response = array();
        $courses = array();
        $allLanguages = array();
        $allSubjects = array();
        $allSessions = array();
        $sortField = $sortClass = $pageNo = '';

        $request = $this->getRequest();
        $keywords = $request->get('q');
        $total = 0;
        if  (!empty($keywords)) {
            // Perform the search
            extract( $cl->search( $keywords, $request ));
            $total = $courses['hits']['total'];
        }

        return $this->render('ClassCentralSiteBundle:Search:index.html.twig', array(
            'page' => 'search', 
            'total' => $total,
            'keywords' => $keywords,
            'results' => $courses,
            'listTypes' => UserCourse::$lists,
            'allSubjects' => $allSubjects,
            'allLanguages' => $allLanguages,
            'allSessions'  => $allSessions,
            'sortField' =>$sortField,
            'sortClass' => $sortClass,
            'pageNo' => $pageNo,
            'showHeader' => true
        ));        
    }
}
