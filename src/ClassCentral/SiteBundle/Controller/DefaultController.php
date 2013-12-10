<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Utility\PageHeader\PageHeaderFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Offering;

class DefaultController extends Controller {
               
    public function indexAction() {
  
        $cache = $this->get('Cache');
        $offerings = $cache->get('default_index_offerings',
                    array ($this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering'),'findAllByInitiative'));                

        return $this->render('ClassCentralSiteBundle:Default:index.html.twig', 
                            array( 'offerings' => $offerings, 'page' => 'home',
                                  'offeringTypes'=> array_intersect_key( Offering::$types, array_flip(array('recent','recentlyAdded')))));
    }
    
    
    
    public function coursesAction($type = 'upcoming'){
        if(!in_array($type, array_keys(Offering::$types))){
            // TODO: render an error page
            return false;
        }
        $cache = $this->get('Cache');
        $filterService = $this->get('Filter');

        $offerings = $cache->get('default_courses_offerings_' . $type,
                    array ($this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering'),'findAllByInitiative'));

        $shownOfferings = array($type => $offerings[$type]); // All offerings are retriviewed but only one type is shown
        $subjects = $cache->get('default_courses_offerings_subjects_' . $type,array($filterService, 'getOfferingSubjects'), array($shownOfferings));
        $lang = $cache->get('default_courses_offerings_languages_' . $type, array($filterService,'getOfferingLanguages'),array($shownOfferings));

        return $this->render('ClassCentralSiteBundle:Default:courses.html.twig', 
                array(
                    'offeringType' => $type,
                    'offerings' => $offerings,
                    'page'=>'courses',
                    'offeringTypes'=> Offering::$types,
                    'offSubjects' => $subjects,
                    'offLanguages' => $lang
                ));
    }

    /**
     * Initiative is now referred to as provider
     * @param string $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function initiativeAction($type='coursera')
    {
        $cache = $this->get('Cache');
        $filterService = $this->get('Filter');

        $initiativeInfo = $cache->get('default_initative_ids_'. $type, array($this, 'getInitiativeIds'), array($type));
        if(empty($initiativeInfo)) {
            return;
        }

        $offerings = $cache->get('default_initiative_offerings_' . $type,
                    array ($this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering'),'findAllByInitiative'), array($initiativeInfo['ids']));

        // TODO: All Subjects and offerings should be in sync
        $subjects = $cache->get('initiative_subjects_' . $type,array($filterService, 'getOfferingSubjects'), array($offerings));
        $lang = $cache->get('initiative_languages_' . $type, array($filterService,'getOfferingLanguages'),array($offerings));


        $pageInfo =  PageHeaderFactory::get($initiativeInfo['initiative']);
        $pageInfo->setPageUrl(
            $this->container->getParameter('baseurl'). $this->get('router')->generate('ClassCentralSiteBundle_initiative', array('type' => $type))
        );
        return $this->render('ClassCentralSiteBundle:Default:initiative.html.twig', 
                array(
                    'initiative' =>$initiativeInfo['initiative'],
                    'offerings' => $offerings,
                    'pageInfo' => $pageInfo,
                    'page'=>'initiative',
                    'initiativeType' => $type,
                    'offeringTypes'=> Offering::$types,
                    'offSubjects' => $subjects,
                    'offLanguages' => $lang
                ));
    }
    
    public function getInitiativeIds($type)
    {
        $initiativeTypes = Initiative::$types;
        $em = $this->getDoctrine()->getManager();

        // Get the initiative id
        $initiativeIds = array();        
        if( $type != 'others'){
            $initiative = $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Initiative')
                    ->findOneByCode(strtoupper($type));
            if(!$initiative)
            {
                return null;
            }
            $em->detach($initiative);
            $initiativeName = $initiative->getName();
            $initiativeIds[] = $initiative->getId();
        } else {
            $initiativeName = 'Others';
            $initiatives = implode("','", array_values($initiativeTypes));
            $query = $em->createQuery("SELECT i FROM ClassCentralSiteBundle:Initiative i WHERE i.code NOT IN ('$initiatives')");
            foreach($query->getResult() as $initiative){
                $initiativeIds[] = $initiative->getId();
            }
            $initiative = new Initiative();
            $initiative->setName($initiativeName);
        }
        
        return array('initiative' => $initiative, 'ids' =>$initiativeIds);
    }

    public function faqAction() {
        return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array('page' => 'faq'));
    }
    
    /**
     * 
     * Cache cant be cleared from the command line. So creating an action
     */
    public function clearCacheAction(){
        $this->get('cache')->clear();
        // Just adding a dummy page
        return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array('page' => 'faq'));
    }
    
}
