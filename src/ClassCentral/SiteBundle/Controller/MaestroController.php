<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/17/14
 * Time: 2:09 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Services\Filter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MaestroController extends Controller {

    public function providerAction(Request $request, $slug)
    {
        $cl = $this->get('course_listing');
        $data = $cl->byProvider($slug,$request);

        return $this->returnJsonResponse(
            $data,
            'providertable',
            'initiative'
        );

    }

    public function subjectAction(Request $request, $slug)
    {
        $cl = $this->get('course_listing');
        $data = $cl->bySubject($slug,$request);

        return $this->returnJsonResponse(
            $data,
            'subjectstable',
            'subject'
        );
    }

    public function coursesAction(Request $request, $type)
    {
        $cl = $this->get('course_listing');
        $data = $cl->byTime($type,$request);

        return $this->returnJsonResponse(
            $data,
            'statustable',
            'courses'
        );
    }

    public function institutionAction(Request $request, $slug)
    {
        $cl = $this->get('course_listing');
        $data = $cl->byInstitution($slug,$request);

        return $this->returnJsonResponse(
            $data,
            'institutiontable',
            'institution'
        );
    }

    public function languageAction(Request $request, $slug)
    {
        $cl = $this->get('course_listing');
        $data = $cl->byLanguage($slug,$request);

        return $this->returnJsonResponse(
            $data,
            'languagetable',
            'language'
        );
    }

    public function tagAction(Request $request, $tag)
    {
        $cl = $this->get('course_listing');
        $data = $cl->byTag($tag,$request);

        return $this->returnJsonResponse(
            $data,
            'tagtable',
            'tag'
        );
    }

    private function returnJsonResponse($data, $tableName, $page )
    {
        extract( $data );

        $table =  $this->render('ClassCentralSiteBundle:Helpers:course.table.html.twig',array(
            'results' => $courses,
            'tableId' => $tableName,
            'listTypes' => UserCourse::$lists,
            'page' => $page,
            'sortField' => $sortField,
            'sortClass' => $sortClass,
            'pageNo'=>$pageNo,
            'showHeader' => false
        ))->getContent();
        $response = array(
            'table' => $table,
            'numCourses' => $courses['hits']['total']
        );

        return new Response( json_encode( $response ) );

    }
} 