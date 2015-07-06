<?php

namespace ClassCentral\CredentialBundle\Controller;

use ClassCentral\SiteBundle\Entity\Profile;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CredentialReviewController extends Controller
{

    public function newAction(Request $request)
    {
        return $this->render('ClassCentralCredentialBundle:CredentialReview:reviewForm.html.twig', array(
            'degrees' => Profile::$degrees,
        ));
    }
}