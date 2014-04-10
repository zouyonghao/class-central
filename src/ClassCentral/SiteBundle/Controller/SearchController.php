<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\UserCourse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Offering;

class SearchController extends Controller{
       
    public function indexAction() {

        $esCourses = $this->get('es_courses');
        $filter =$this->get('filter');

        $response = array();
        $response['results'] = array();
        $allLanguages = array();
        $allSubjects = array();


        $request = $this->getRequest();
        $keywords = $request->get('q');
        $total = 0;
        if  (!empty($keywords)) {
            // Perform the search

            $response = $esCourses->search($keywords);
            $allSubjects = $filter->getCourseSubjects($response['subjectIds']);
            $allLanguages = $filter->getCourseLanguages($response['languageIds']);
            $total = $response['results']['hits']['total'];
        }
                

        return $this->render('ClassCentralSiteBundle:Search:index.html.twig', array(
            'page' => 'search', 
            'total' => $total,
            'keywords' => $keywords,
            'results' => $response['results'],
            'listTypes' => UserCourse::$lists,
            'allSubjects' => $allSubjects,
            'allLanguages' => $allLanguages,
        ));        
    }
}
