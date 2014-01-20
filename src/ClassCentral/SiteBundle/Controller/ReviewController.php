<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 1/19/14
 * Time: 4:05 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Entity\Review;
use ClassCentral\SiteBundle\Entity\UserCourse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ReviewController extends Controller {

    /**
     * Renders the form to create a new review
     * @param Request $request
     * @param $courseId
     */
    public function newAction(Request $request, $courseId) {
        // Get the course
        $em = $this->getDoctrine()->getManager();
        $course = $em->getRepository('ClassCentralSiteBundle:Course')->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        return $this->render('ClassCentralSiteBundle:Review:new.html.twig', array(
            'page' => 'write_review',
            'progress' => UserCourse::$progress,
            'difficulty'=> Review::$difficulty,
            'course' => $course
        ));
    }
} 