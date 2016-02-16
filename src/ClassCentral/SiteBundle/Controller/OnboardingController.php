<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 2/15/16
 * Time: 3:02 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class OnboardingController extends Controller
{

    public function stepProfileAction(Request $request)
    {
        $html = $this->render('ClassCentralSiteBundle:Onboarding:profile.html.twig', array())->getContent();

        $response = array(
            'modal' => $html
        );

        return new Response( json_encode($response) );
    }
}