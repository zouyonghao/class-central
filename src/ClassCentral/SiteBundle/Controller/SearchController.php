<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\UserCourse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Offering;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller{
       
    public function indexAction(Request $request)
    {
        $cl = $this->get('course_listing');

        $response = array();
        $courses = array();
        $allLanguages = array();
        $allSubjects = array();
        $allSessions = array();
        $numCoursesWithCertificates = 0;
        $sortField = $sortClass = $pageNo = '';

        $request = $this->getRequest();
        $keywords = trim($request->get('q'));
        $total = 0;
        if  (!empty($keywords)) {
            // Perform the search
            extract( $cl->search( $keywords, $request ));
            $total = $courses['hits']['total'];
        }

        $pageMetadata = [
            'search_keywords' => $keywords
        ];

        return $this->render('ClassCentralSiteBundle:Search:index.html.twig', array(
            'page' => 'search', 
            'total' => $total,
            'keywords' => $keywords,
            'results' => $courses,
            'listTypes' => UserCourse::$lists,
            'allSubjects' => $allSubjects,
            'allLanguages' => $allLanguages,
            'allSessions'  => $allSessions,
            'numCoursesWithCertificates' => $numCoursesWithCertificates,
            'sortField' =>$sortField,
            'sortClass' => $sortClass,
            'pageNo' => $pageNo,
            'showHeader' => true,
            'pageMetadata' => $pageMetadata
        ));        
    }

    /**
     * Returns the results for search box autocomplete
     * @param Request $request
     * @param $query
     */
    public function autocompleteAction(Request $request, $query)
    {
        $esClient = $this->container->get('es_client');
        $indexName = $this->container->getParameter( 'es_index_name' );

        $params['index'] = $indexName;
        $params['body'] = array();
        $params['body']['autocomplete'] = array(
            "text" => $query,
            "completion" => array(
                'size' => 10,
                "field" => "name_suggest"
            )
        );

        $results = $esClient->suggest( $params );
        return new Response( json_encode($results) );
    }

    public function autocompleteCourseAction(Request $request, $query)
    {
        $finder = $this->container->get('course_finder');
        $courses = [];
        if(strlen($query) >= 3)
        {
            $results = $finder->courseAutoComplete($query);
            $totalCourses = 0;
            foreach ($results['hits']['hits'] as $course)
            {
                if($totalCourses >=8)
                {
                    break;
                }
                $course = $course['_source'];
                $ins = null;
                if(!empty($course['institutions']))
                {
                    $ins =  $course['institutions'][0]['name'];
                }

                $courses [] = [
                    'id' => $course['id'],
                    'name' => $course['name'],
                    'provider' => $course['provider']['name'],
                    'institution' => $ins
                ];

                $totalCourses++;
            }
        }


        return new Response( json_encode($courses) );
    }
}
