<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Course;

class DefaultController extends Controller {

    public function indexAction() {

        $now = new \DateTime;
        
        $em = $this->getDoctrine()->getEntityManager();

        //$offering = new \ClassCentral\SiteBundle\Entity\Offering();
        // Ongoing
        $query = $em->createQueryBuilder();
        $query->add('select', 'o')
                ->add('from', 'ClassCentralSiteBundle:Offering o')
                ->add('where', 'o.startDate <= :datetime AND o.status = :status AND (o.endDate >= :datetime OR o.endDate IS NULL)' )
		->add('orderBy','o.startDate DESC')
                ->setParameter('datetime', $now->format("Y-m-d"))
                ->setParameter('status',\ClassCentral\SiteBundle\Entity\Offering::START_DATES_KNOWN)
        ;
        $ongoing = $query->getQuery()->getResult();
        
        // Past
        $query = $em->createQueryBuilder();
        $query->add('select', 'o')
                ->add('from', 'ClassCentralSiteBundle:Offering o')
                ->add('where', 'o.endDate < :datetime')
                ->setParameter('datetime', $now->format("Y-m-d"))
        ;
        $past = $query->getQuery()->getResult();

        // Upcoming
        $query = $em->createQueryBuilder();
        $query->add('select', 'o')
                ->add('from', 'ClassCentralSiteBundle:Offering o')
                ->add('where', 'o.startDate > :datetime')
		->add('orderBy','o.startDate')
                ->setParameter('datetime', $now->format("Y-m-d"))
        ;
        $upcoming = $query->getQuery()->getResult();

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
                

        return $this->render('ClassCentralSiteBundle:Default:index.html.twig', 
                            array('ongoing' => $ongoing, 'upcoming' => $upcoming,'past' => $past, 'stats' => $stats, 'page' => 'home', 'initiatives' => $initiatives));
    }

    public function faqAction() {
        return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array('page' => 'faq'));
    }

}
