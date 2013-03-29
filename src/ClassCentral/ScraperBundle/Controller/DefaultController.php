<?php

namespace ClassCentral\ScraperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction($name)
    {
        return $this->render('ClassCentralScraperBundle:Default:index.html.twig', array('name' => $name));
    }
}
