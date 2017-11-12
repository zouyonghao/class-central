<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 11/11/17
 * Time: 9:41 PM
 */

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class LandingPageController extends Controller
{
    public function voteBestOnlineCourses2017Action(Request $request)
    {
        $this->get('user_service')->autoLogin($request);
        return $this->render('ClassCentralSiteBundle:LandingPages:voteBestOnlineCourses2017.html.twig', array(
            'page' => 'top-courses',
            'subjects' => $this->getSubjectList()
        ));
    }

    private function getSubjectList()
    {
        return [
            [
                'name' => 'Computer Science',
                'slug' => 'cs',
                'numCourses' => '218'
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'numCourses' => '315'
            ],
            [
                'name' => 'Humanities',
                'slug' => 'humanities',
                'numCourses' => '167'
            ],
            [
                'name' => 'Data Science',
                'slug' => 'data-science',
                'numCourses' => '77'
            ],
            [
                'name' => 'Personal Development',
                'slug' => 'personal-development',
                'numCourses' => '61'
            ],
            [
                'name' => 'Programming',
                'slug' => 'programming-and-software-development',
                'numCourses' => '161'
            ],
            [
                'name' => 'Art and Design',
                'slug' => 'art-and-design',
                'numCourses' => '104'
            ],
            [
                'name' => 'Health',
                'slug' => 'health',
                'numCourses' => '123'
            ],
            [
                'name' => 'Mathematics',
                'slug' => 'maths',
                'numCourses' => '40'
            ],
            [
                'name' => 'Engineering',
                'slug' => 'engineering',
                'numCourses' => '146'
            ],
            [
                'name' => 'Science',
                'slug' => 'science',
                'numCourses' => '167'
            ],
            [
                'name' => 'Education & Teaching',
                'slug' => 'education',
                'numCourses' => '144'
            ],
            [
                'name' => 'Social Sciences',
                'slug' => 'social-sciences',
                'numCourses' => '224'
            ],
        ];
    }
}