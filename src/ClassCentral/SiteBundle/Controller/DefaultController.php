<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {

    public function indexAction() {

        // Not being shown currently
        /* 
        // Get some stats
        $stats['courses'] = $em->createQuery('SELECT COUNT(c.id) FROM ClassCentralSiteBundle:Course c')->getSingleScalarResult();
        $stats['instructors'] = $em->createQuery('SELECT COUNT(i.id) FROM ClassCentralSiteBundle:Instructor i')->getSingleScalarResult();

        // Get course counts by initiative
        $initiatives = $em->createQueryBuilder()->addSelect('ini.name, count(o) AS offerings')
                        ->from('ClassCentralSiteBundle:Initiative', 'ini')
                        ->leftjoin('ini.offerings', 'o')
                        ->where('o.startDate > :datetime')
                        ->addGroupBy('ini.id')
                        ->setParameter('datetime', $now->format("Y-m-d"))
                        ->getQuery()->getArrayResult();   
         */

        $offerings = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByInitiative();

        return $this->render('ClassCentralSiteBundle:Default:index.html.twig', 
                            array_merge( $offerings, array( 'page' => 'home' )));
    }

    public function faqAction() {
        return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array('page' => 'faq'));
    }
    
}
