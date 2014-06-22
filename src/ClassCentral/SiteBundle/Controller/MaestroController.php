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
            'providertable'
        );

    }

    public function subjectAction(Request $request, $slug)
    {
        $cl = $this->get('course_listing');
        $data = $cl->bySubject($slug,$request);

        return $this->returnJsonResponse(
            $data,
            'subjectstable'
        );
    }

    private function returnJsonResponse($data, $tableName )
    {
        extract( $data );

        $table =  $this->render('ClassCentralSiteBundle:Helpers:course.table.html.twig',array(
            'results' => $courses,
            'tableId' => 'providertable',
            'listTypes' => UserCourse::$lists,
            'page' => 'initiative',
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