<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\Offering;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\Course;
use ClassCentral\SiteBundle\Form\CourseType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Email;

/**
 * Course controller.
 *
 */
class CourseController extends Controller
{
    /**
     * Lists all Course entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:Course')->findAll();

        return $this->render('ClassCentralSiteBundle:Course:index.html.twig', array(
            'entities' => $entities
        ));
    }
    
    /**
     *  List all Course entities filtered by intiative
     */
    
    public function initiativeAction($initiative)
    {
        $em = $this->getDoctrine()->getManager();
        $initiative = $em->getRepository('ClassCentralSiteBundle:Initiative')->findOneByCode($initiative);
        
        $entities = $em->getRepository('ClassCentralSiteBundle:Course')->findByInitiative($initiative->getId());

        return $this->render('ClassCentralSiteBundle:Course:index.html.twig', array(
            'entities' => $entities
        ));
        
    }

    /**
     * Finds and displays a Course entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }
        
        $offerings = $em->getRepository('ClassCentralSiteBundle:Offering')->findByCourse($id);

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Course:show.html.twig', array(
            'entity'      => $entity,
            'offerings' => $offerings,
            'delete_form' => $deleteForm->createView(),

        ));
    }

    /**
     * Displays a form to create a new Course entity.
     *
     */
    public function newAction()
    {
        $entity = new Course();
        $form   = $this->createForm(new CourseType(), $entity);

        return $this->render('ClassCentralSiteBundle:Course:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Creates a new Course entity.
     *
     */
    public function createAction()
    {
        $entity  = new Course();        
        $request = $this->getRequest();
        $form    = $this->createForm(new CourseType(), $entity);
        $form->handleRequest($request);
        if ($form->isValid()) {                                  
            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('course_show', array('id' => $entity->getId())));
            
        }

        return $this->render('ClassCentralSiteBundle:Course:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Displays a form to edit an existing Course entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        $editForm = $this->createForm(new CourseType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Course:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Course entity.
     *
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Course entity.');
        }

        $editForm   = $this->createForm(new CourseType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('course_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:Course:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Course entity.
     *
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ClassCentralSiteBundle:Course')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Course entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('course'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    /**
     *
     * @param $id Row id for the course
     * @param $slug descriptive url for the course
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function moocAction($id, $slug)
    {
       $em = $this->getDoctrine()->getManager();
       $courseId = intval($id);
       $course = $this->get('Cache')->get( 'course_' . $courseId, array($this,'getCourseDetails'), array($courseId,$em) );
       if(!$course)
       {
           // TODO: render a error page
          return;
       }

       // If the slug is not the same, then redirect to the correct url

        if( $course['slug'] !== $slug)
        {
            $url = $this->container->getParameter('baseurl') . $this->get('router')->generate('ClassCentralSiteBundle_mooc', array('id' => $course['id'],'slug' => $course['slug']));
            return $this->redirect($url,301);
        }


        // Save the course and user tracking for generating recommendations later on
       if($this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
       {
           $user = $this->get('security.context')->getToken()->getUser();
           $sessionId = $user->getId();
       }
       else
       {
           $session = $this->getRequest()->getSession();
           if(!$session->isStarted())
           {
               // Start the session if its not already started
               $session->start();
           }
           $sessionId = $session->getId();
       }
       $em->getConnection()->executeUpdate("INSERT INTO user_courses_tracking(user_identifier,course_id)
                                VALUES ('$sessionId', $courseId)");

       // Recently viewed
       $userSession = $this->get('user_session');
       $recentlyViewedCourseIds = $userSession->getRecentlyViewed();
       $recentlyViewedCourses = array();
       if(!empty($recentlyViewedCourseIds))
       {
           foreach($recentlyViewedCourseIds as $id)
           {
               $recentlyViewedCourses[] = $this->get('Cache')->get( 'course_' . $id, array($this,'getCourseDetails'), array($id,$em) );
           }
       }
       $userSession->saveRecentlyViewed($courseId);

       // URL of the current page
       $course['pageUrl'] = $this->container->getParameter('baseurl') . $this->get('router')->generate('ClassCentralSiteBundle_mooc', array('id' => $course['id'],'slug' => $course['slug']));

       // Page Title/Twitter card title
        $titlePrefix = '';
        if(!empty($course['initiative']['name']))
        {
            $titlePrefix = ' from ' . $course['initiative']['name'];
        }
        $course['pageTitle'] = $course['name'] . $titlePrefix;

        if(strlen($course['desc']) > 500)
        {
            $course['desc'] = substr($course['desc'],0,497) . '...';
        }

        // Figure out if there is course in the future.
        $nextSession = null;
        $nextSessionStart ='';
        if(count($course['offerings']['upcoming']) > 0)
        {
            $nextSession = $course['offerings']['upcoming'][0];
            $nextSessionStart = $nextSession['displayDate'];
        }

       return $this->render(
           'ClassCentralSiteBundle:Course:mooc.html.twig',
           array('page' => 'home',
                 'course'=>$course,
                 'offeringTypes' => Offering::$types,
                 'offeringTypesOrder' => array('upcoming','ongoing','selfpaced','past'),
                 'nextSession' => $nextSession,
                 'nextSessionStart' => $nextSessionStart,
                 'recentlyViewedCourses' => $recentlyViewedCourses
       ));
    }

    public function shareAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $mailgun = $this->get('mailgun');
        $validator = $this->get('validator');
        $request = $this->getRequest();

        // Get the request params
        $to = $request->request->get('to');
        $name = $request->request->get('name');
        $from = $request->request->get('from');

        $courseId = intval($id);
        $course = $this->get('Cache')->get( 'course_' . $courseId, array($this,'getCourseDetails'), array($courseId,$em) );

        $errors = array();
        if(!$course)
        {
            $errors[] = 'Course does not exist';
        }

        // Check if $from, $to fields are valid
        $emailConstraint = new Email();
        $emailConstraint->message = 'Invalid email address';
        $toErrorList = $validator->validateValue($to,$emailConstraint);
        $fromErrorList = $validator->validateValue($from,$emailConstraint);
        if(count($toErrorList) != 0 )
        {
            $errors[] = 'Invalid TO email address';
        }
        if(count($fromErrorList) != 0) {
            $errors[] = 'Invalid FROM email address';
        }

        if(empty($name))
        {
            $errors[] = 'Name is a required field';
        }

        if(!empty($errors))
        {
            $response = new Response(json_encode(array('errors' => $errors,'success'=>false)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $subject = $course['name'];
        if(!empty($course['initiative']) && !empty($course['initiative']['name']))
        {
            $subject = $course['initiative']['name'] . ' - ' .  $course['name'];
        }

        $mailgunResponse = $mailgun->sendSimpleText($to,"{$name}<{$from}>", $subject,$this->formatCourseEmailMessage($course,$name));
        $mailgunResponseArray = json_decode($mailgunResponse,true);

        $responseArray = array();
        if(!isset($mailgunResponse['id']))
        {
           $responseArray['errors'][] = "Some error occurred. Please try again";
           $responseArray['success'] = false;
        } else {
            $responseArray['success'] = true;
        }

        $response = new Response(json_encode($responseArray));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }


    private function formatCourseEmailMessage($course, $name)
    {
        $url = $this->container->getParameter('baseurl') . $this->get('router')->generate('ClassCentralSiteBundle_mooc', array('id' => $course['id'],'slug' => $course['slug']));

        $text = <<< EOD
$name shared this free online course/MOOC "{$course['name']}" with you via Class Central.

COURSE DESCRIPTION:
{$course['desc']}


EOD;
        if(count($course['offerings']['upcoming']) > 0)
        {
            $nextSession = $course['offerings']['upcoming'][0];
            $nextSessionStart = $nextSession['displayDate'];
            $text = $text . 'Next session start date: '. $nextSession['displayDate'];

        }

        $text = <<< EOD
$text

Find more details about the course at $url

---
For a complete list of courses please visit Class Central at http://www.class-central.com.
EOD;

        return $text;
    }

     /**
     * Retrieves the course details and offerings
     * @param $courseId
     */
    public function getCourseDetails($courseId, $em)
    {
        // Get the course first
        $courseEntity = $em->getRepository('ClassCentralSiteBundle:Course')->findOneById($courseId);
        if(!$courseEntity)
        {
            // Invalid course
            return null;
        }
        $courseDetails = $em->getRepository('ClassCentralSiteBundle:Course')->getCourseArray($courseEntity);
        // Course exists get all the offerings
        $courseDetails['offerings'] = $em->getRepository('ClassCentralSiteBundle:Offering')->findAllByCourseIds(array($courseId));

        // Flip the past courses to show the newest ones first
        // TODO: Sort these courses correctly
        foreach($courseDetails['offerings'] as $type => $courses)
        {
            $courseDetails['offerings'][$type] =  array_reverse($courses);
        }

        return $courseDetails;
    }
}
