<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AboutController extends Controller
{
    public function privacyAction(Request $request)
    {
        $this->get('user_service')->autoLogin($request);
        return $this->render('ClassCentralSiteBundle:About:privacy.html.twig', ['page' => 'privacy_policy']);
    }

    public function cookiesAction(Request $request)
    {
        return $this->render('ClassCentralSiteBundle:About:cookies_policy.html.twig', ['page' => 'cookies_policy']);
    }

    public function thirdPartyAction(Request $request)
    {
        return $this->render('ClassCentralSiteBundle:About:third_party.html.twig', ['page' => 'third_party_service_providers_and_cookies']);
    }
}
