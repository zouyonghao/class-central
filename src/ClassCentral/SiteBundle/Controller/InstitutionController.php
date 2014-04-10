<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Utility\PageHeader\PageHeaderFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\Institution;
use ClassCentral\SiteBundle\Form\InstitutionType;
use ClassCentral\SiteBundle\Entity\Offering;

/**
 * Institution controller.
 *
 */
class InstitutionController extends Controller
{
    /**
     * Lists all Institution entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:Institution')->findAll();

        return $this->render('ClassCentralSiteBundle:Institution:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Finds and displays a Institution entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Institution')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Institution entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Institution:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),

        ));
    }

    /**
     * Displays a form to create a new Institution entity.
     *
     */
    public function newAction()
    {
        $entity = new Institution();
        $form   = $this->createForm(new InstitutionType(), $entity);

        return $this->render('ClassCentralSiteBundle:Institution:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Creates a new Institution entity.
     *
     */
    public function createAction()
    {
        $entity  = new Institution();
        $request = $this->getRequest();
        $form    = $this->createForm(new InstitutionType(), $entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('institution_show', array('id' => $entity->getId())));
            
        }

        return $this->render('ClassCentralSiteBundle:Institution:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Displays a form to edit an existing Institution entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Institution')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Institution entity.');
        }

        $editForm = $this->createForm(new InstitutionType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Institution:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Institution entity.
     *
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Institution')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Institution entity.');
        }

        $editForm   = $this->createForm(new InstitutionType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('institution_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:Institution:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Institution entity.
     *
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ClassCentralSiteBundle:Institution')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Institution entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('institution'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
    
    public function viewAction($slug) {


        $cache = $this->get('cache');

        $data = $cache->get(
            'institution_' . $slug,
            function($slug, $container) {
                $esCourses = $this->get('es_courses');
                $filter =$this->get('filter');
                $em = $container->get('doctrine')->getManager();

                $institution = $em->getRepository('ClassCentralSiteBundle:Institution')->findOneBySlug($slug);
                if(!$institution) {
                    // TODO: render an error page
                    return array();
                }

                $pageInfo =  PageHeaderFactory::get($institution);
                $pageInfo->setPageUrl(
                    $container->getParameter('baseurl'). $container->get('router')->generate('ClassCentralSiteBundle_institution', array('slug' => $slug))
                );

                $response = $esCourses->findByInstitution($slug);
                $allSubjects = $filter->getCourseSubjects($response['subjectIds']);
                $allLanguages = $filter->getCourseLanguages($response['languageIds']);
                $allSessions  = $filter->getCourseSessions( $response['sessions'] );

                return array(
                    'response' => $response,
                    'institution' => $institution,
                    'pageInfo' => $pageInfo,
                    'allSubjects' => $allSubjects,
                    'allLanguages' => $allLanguages,
                    'allSessions'  => $allSessions
                );
            },
            array($slug, $this->container)
        );

        if( empty($data) )
        {
            // Show an error message
            return;
        }


        return $this->render('ClassCentralSiteBundle:Institution:view.html.twig', 
                array(
                    'institution' => $data['institution'],
                    'page'=>'institution',
                    'slug' => $slug,
                    'results' => $data['response']['results'],
                    'listTypes' => UserCourse::$lists,
                    'allSubjects' => $data['allSubjects'],
                    'allLanguages' => $data['allLanguages'],
                    'allSessions' => $data['allSessions'],
                    'pageInfo' => $data['pageInfo']
                ));                
    }
    
    public function getOfferingsByInstitution( \ClassCentral\SiteBundle\Entity\Institution $institution) {
        // List of all the courses offered by the this particular institution
        $courses = $institution->getCourses();
        
        // Get all the course id
        $courseIds = array();
        foreach ($courses as $course){
            $courseIds[] = $course->getId();
        }
        
        return $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByCourseIds($courseIds);
    }
}
