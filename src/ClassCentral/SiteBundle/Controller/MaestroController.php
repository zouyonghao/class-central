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
        $finder = $this->get('course_finder');
        $em = $this->get('doctrine')->getManager();

        if( $slug == 'others')
        {
            $provider = new Initiative();
            $provider->setName('Others');
            $provider->setCode('others');
        }
        elseif ( $slug == 'independent')
        {
            $provider = new Initiative();
            $provider->setName('Independent');
            $provider->setCode('independent');
        }
        else
        {
            $provider  = $em->getRepository('ClassCentralSiteBundle:Initiative')->findOneBy( array('code'=>$slug ) );
            if(!$provider)
            {
                throw new \Exception("Provider not found");
            }

        }

        $filters = Filter::getQueryFilters( $request->query->all() );
        $courses = $finder->byProvider( $slug, $filters  );

        $table =  $this->render('ClassCentralSiteBundle:Helpers:course.table.html.twig',array(
            'results' => $courses,
            'tableId' => 'providertable',
            'listTypes' => UserCourse::$lists,
            'page' => 'initiative',
        ))->getContent();
        $response = array(
            'table' => $table,
            'numCourses' => $courses['hits']['total']
        );

        return new Response( json_encode( $response ) );

    }
} 