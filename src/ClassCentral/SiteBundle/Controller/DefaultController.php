<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Utility\Breadcrumb;
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
                            array( 'offerings' => $offerings, 'page' => 'home',   'listTypes' => UserCourse::$lists,
                                  'offeringTypes'=> array_intersect_key( Offering::$types, array_flip(array('recent','recentlyAdded')))));
    }
    
    
    
    public function coursesAction($type = 'upcoming'){
        if(!in_array($type, array_keys(Offering::$types))){
            // TODO: render an error page
            return false;
        }

        $cache = $this->get('cache');


        $data = $cache->get(
            'course_status_' . $type,
            function($type, $container) {
                $esCourses = $this->get('es_courses');
                $filter =$this->get('filter');

                $response = $esCourses->findByTime($type);
                $allSubjects = $filter->getCourseSubjects($response['subjectIds']);
                $allLanguages = $filter->getCourseLanguages($response['languageIds']);

                return array(
                    'response' => $response,
                    'allSubjects' => $allSubjects,
                    'allLanguages' => $allLanguages
                );
            },
            array($type, $this->container)
        );

        if( empty($data) )
        {
            // Show an error message
            return;
        }

        return $this->render('ClassCentralSiteBundle:Default:courses.html.twig', 
                array(
                    'offeringType' => $type,
                    'page'=>'courses',
                    'results' => $data['response']['results'],
                    'listTypes' => UserCourse::$lists,
                    'allSubjects' => $data['allSubjects'],
                    'allLanguages' => $data['allLanguages'],
                     'offeringTypes' => Offering::$types
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
                    'offLanguages' => $lang,
                    'listTypes' => UserCourse::$lists
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
        $breadcrumbs = array();
        $breadcrumbs[] = Breadcrumb::getBreadCrumb('FAQ','');
        return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array(
            'page' => 'faq',
            'breadcrumb' => $breadcrumbs
        ));
    }

    public function privacyPolicyAction() {
        return $this->render('ClassCentralSiteBundle:Default:privacy.html.twig', array(
            'page' => 'privacy',
        ));
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
