<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\Spotlight;
use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Form\SignupType;
use ClassCentral\SiteBundle\Utility\Breadcrumb;
use ClassCentral\SiteBundle\Utility\PageHeader\PageHeaderFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\Offering;

class DefaultController extends Controller {
               
    public function indexAction() {
  
        $cache = $this->get('Cache');
        $recent = $cache->get('course_status_recent', array($this, 'getCoursesByStatus'), array('recent', $this->container));
        $esCourses = $this->get('es_courses');
        $em = $this->getDoctrine()->getManager();

        $spotlights = $cache->get('spotlight_cache',function(){
           $s = $this
                ->getDoctrine()->getManager()
                ->getRepository('ClassCentralSiteBundle:Spotlight')->findAll();

            $spotlights = array();
            foreach($s as $item)
            {
                $spotlights[$item->getPosition()] = $item;
            }

            return $spotlights;
        }, array());


        // limit the results to 10 courses
        $recent['response']['results']['hits']['hits'] =
            array_splice($recent['response']['results']['hits']['hits'],0,10);

        $subjects = $cache->get('stream_list_count',
                        array( new StreamController(), 'getSubjectsList'),
                        array( $this->container )
        );

        $signupForm   = $this->createForm(new SignupType(), new User(),array(
            'action' => $this->generateUrl('signup_create_user')
        ));

        // Get a list of courses taken by the signed in user
        $uc = array();
        $ucCount = 0;
        if( $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY') )
        {
            $user = $this->get('security.context')->getToken()->getUser();

            $qb = $em->createQueryBuilder();
            $qb
                ->add('select', 'c.id as cid')
                ->add('from','ClassCentralSiteBundle:UserCourse uc')
                ->join('uc.course', 'c')
                ->andWhere('uc.user = :userId')
                ->setParameter('userId',$user->getId() )
                ->add('orderBy', 'uc.id DESC') // newest one top
                ;
            $results = $qb->getQuery()->getArrayResult();
            $courseIds = array();
            foreach($results as $result)
            {
                $courseIds[] = $result['cid'];
            }

            $ucCount = count( $courseIds );
            if( !empty($courseIds) )
            {
                $courseIds = array_splice( $courseIds, 0 , 10);
            }
            $response = $esCourses->findByIds( $courseIds );
            $uc = $response['results'];
        }


        return $this->render('ClassCentralSiteBundle:Default:index.html.twig', array(
                'page' => 'home',
                'listTypes' => UserCourse::$lists,
                'recentCourses'   => $recent['response']['results'],
                'spotlights' => $spotlights,
                'spotlightMap' => Spotlight::$spotlightMap,
                'subjects' => $subjects,
                'signupForm' => $signupForm->createView(),
                'uc' => $uc,
                'ucCount' => $ucCount
               ));
    }


    public function getCoursesByStatus($type, $container)
    {
        $esCourses = $container->get('es_courses');
        $filter =$container->get('filter');
        $response = $esCourses->findByTime($type);
        $allSubjects = $filter->getCourseSubjects($response['subjectIds']);
        $allLanguages = $filter->getCourseLanguages($response['languageIds']);

        return array(
            'response' => $response,
            'allSubjects' => $allSubjects,
            'allLanguages' => $allLanguages
        );
    }
    
    public function coursesAction($type = 'upcoming')
    {
        if(!in_array($type, array_keys(Offering::$types))){
            // TODO: render an error page
            return false;
        }

        $cache = $this->get('cache');


        $data = $cache->get(
            'course_status_' . $type,
            array($this, 'getCoursesByStatus'),
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
        $breadcrumbs = array(
            Breadcrumb::getBreadCrumb('FAQ', $this->container->get('router')->generate('ClassCentralSiteBundle_faq')),
        );
        return $this->render('ClassCentralSiteBundle:Default:faq.html.twig', array(
            'page' => 'faq',
            'breadcrumbs' => $breadcrumbs
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

    public function githubButtonAction()
    {
        if ($this->container->has('profiler'))
        {
            $this->container->get('profiler')->disable();
        }
        return $this->render('ClassCentralSiteBundle:Default:githubbtn.html.twig');
    }
    
}
