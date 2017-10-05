<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\ElasticSearchBundle\DocumentType\CourseDocumentType;
use ClassCentral\SiteBundle\Entity\Course;
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
        $courseInfo = array();
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

        if (empty($keywords)) {
            // Courses with most follows that have been added recently
            $newAndPopular = $this->getNewAndPopularCourses();
            $newAndPopular = $newAndPopular['hits']['hits'];

            // Courses sorted by follows
            $popularCourses = $this->getPopularCourses();
            $popularCourses = $popularCourses['hits']['hits'];

            $courseInfo = [
                [
                    'title' => 'New',
                    'courses' => $newAndPopular,
                ],
                [
                    'title' => 'Trending',
                    'courses' => $popularCourses,
                ]
            ];
        }

        return $this->render('ClassCentralSiteBundle:Search:index.html.twig', array(
            'page' =>  empty($keywords) ? 'empty_search' : 'search',
            'total' => $total,
            'footer' => empty($keywords) || $total == 0 ? 'basic' : 'full',
            'navbarStyle' => empty($keywords) ? 'simple' : null,
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
            'pageMetadata' => $pageMetadata,
            'courseInfo' => $courseInfo,
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

    private function getNewAndPopularCourses()
    {
        $cp = $this->container->get('es_cp');
        $sortOrder = array();
        $sortOrder [] = array(
            'followed' => array(
                'order' => 'desc'
            )
        );
        $query = array(

            'term' => [
                'nextSession.states' => 'recentlyadded'
                ]

        );

        return $cp->find( $query, [],[], $sortOrder );
    }

    private function getPopularCourses()
    {
        $cp = $this->container->get('es_cp');
        $sortOrder = [];
        $sortOrder[] = [
            'followed' => [
                'order' => 'desc'
            ]
        ];
        $query = [
            'terms' => [
                'nextSession.states' => ['upcoming', 'selfpaced', 'ongoing']
            ]
        ];

        return $cp->find( $query, [],[], $sortOrder );
    }
}
