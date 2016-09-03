<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 9/2/16
 * Time: 10:50 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TagController extends Controller
{
    /**
     * Lists all the tags
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $tags = $em->getRepository('ClassCentralSiteBundle:Tag')->findAll();

        return $this->render('ClassCentralSiteBundle:Tag:index.html.twig', array(
            'tags' => $tags,
        ));
    }
}