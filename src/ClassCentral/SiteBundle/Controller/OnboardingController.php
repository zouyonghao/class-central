<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 2/15/16
 * Time: 3:02 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Entity\Profile;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class OnboardingController extends Controller
{

    public function stepProfileAction(Request $request)
    {
        $user = $this->getUser();
        $profile = ($user->getProfile()) ? $user->getProfile() : new Profile();

        $html = $this->render('ClassCentralSiteBundle:Onboarding:profile.html.twig',
            array(
                'user' => $user,
                'profile'=> $profile,
                'degrees' => Profile::$degrees,
            ))
            ->getContent();

        $response = array(
            'modal' => $html
        );

        return new Response( json_encode($response) );
    }
}