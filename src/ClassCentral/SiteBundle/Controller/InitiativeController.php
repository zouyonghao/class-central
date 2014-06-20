<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Services\Filter;
use ClassCentral\SiteBundle\Utility\PageHeader\PageHeaderFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Form\InitiativeType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Initiative controller.
 *
 */
class InitiativeController extends Controller
{
    /**
     * Lists all Initiative entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:Initiative')->findAll();

        return $this->render('ClassCentralSiteBundle:Initiative:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Finds and displays a Initiative entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Initiative')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Initiative entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Initiative:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),

        ));
    }

    /**
     * Displays a form to create a new Initiative entity.
     *
     */
    public function newAction()
    {
        $entity = new Initiative();
        $form   = $this->createForm(new InitiativeType(), $entity);

        return $this->render('ClassCentralSiteBundle:Initiative:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Creates a new Initiative entity.
     *
     */
    public function createAction()
    {
        $entity  = new Initiative();
        $request = $this->getRequest();
        $form    = $this->createForm(new InitiativeType(), $entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('initiative_show', array('id' => $entity->getId())));
            
        }

        return $this->render('ClassCentralSiteBundle:Initiative:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Displays a form to edit an existing Initiative entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Initiative')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Initiative entity.');
        }

        $editForm = $this->createForm(new InitiativeType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Initiative:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Initiative entity.
     *
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Initiative')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Initiative entity.');
        }

        $editForm   = $this->createForm(new InitiativeType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('initiative_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:Initiative:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Initiative entity.
     *
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ClassCentralSiteBundle:Initiative')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Initiative entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('initiative'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }


    /**
     * Display the provider page
     * @param $slug
     */
    public function providerAction(Request $request, $type)
    {
        $cache = $this->get('cache');

        $data = $cache->get(
            'provider_' . $type . $request->server->get('QUERY_STRING'),
             function ( $slug, $container, $request ) {
                 $esCourses = $this->get('es_courses');
                 $finder = $this->get('course_finder');
                 $filter =$this->get('filter');
                 $em = $container->get('doctrine')->getManager();

                 if( $slug == 'others')
                 {
                     $provider = new Initiative();
                     $provider->setName('Others');
                     $provider->setCode('others');
                 }
                 elseif ( $slug == 'independent')
                 {
                     $provider = new Initiative();
                     $provider->setName('Independent');
                     $provider->setCode('independent');
                 }
                 else
                 {
                     $provider  = $em->getRepository('ClassCentralSiteBundle:Initiative')->findOneBy( array('code'=>$slug ) );
                     if(!$provider)
                     {
                         return array();
                     }
                 }

                 $pageInfo =  PageHeaderFactory::get($provider);

                 $params = $request->query->all();
                 $filters = Filter::getQueryFilters( $params );
                 $sort    = Filter::getQuerySort( $params );
                 $pageNo = Filter::getPage( $params );
                 $courses = $finder->byProvider( $slug, $filters, $sort, $pageNo );

                 $sortField = '';
                 $sortClass = '';
                 if( isset($params['sort']) )
                 {
                     $sortDetails = Filter::getSortFieldAndDirection( $params['sort'] );
                     $sortField = $sortDetails['field'];
                     $sortClass = Filter::getSortClass( $sortDetails['direction'] );
                 }

                 $response = $esCourses->findByProvider($slug);
                 $allSubjects = $filter->getCourseSubjects( $response['subjectIds'] );
                 $allLanguages = $filter->getCourseLanguages( $response['languageIds'] );
                 $allSessions  = $filter->getCourseSessions( $response['sessions'] );

                 return array(
                     'response' => $response,
                     'provider' => $provider,
                     'pageInfo' => $pageInfo,
                     'allSubjects' => $allSubjects,
                     'allLanguages' => $allLanguages,
                     'allSessions'  => $allSessions,
                     'courses' => $courses,
                     'sortField' => $sortField,
                     'sortClass' => $sortClass,
                     'pageNo' => $pageNo
                 );
             },
            array( $type, $this->container, $request)
        );

        if( empty($data) )
        {
            // Show an error message
            return;
        }

        return $this->render('ClassCentralSiteBundle:Initiative:provider.html.twig',array(
            'results' => $data['courses'],
            'listTypes' => UserCourse::$lists,
            'allSubjects' => $data['allSubjects'],
            'allLanguages' => $data['allLanguages'],
            'allSessions' => $data['allSessions'],
            'page' => 'initiative',
            'provider' => $data['provider'],
            'pageInfo' => $data['pageInfo'],
            'sortField' => $data['sortField'],
            'sortClass' => $data['sortClass'],
            'pageNo' => $data['pageNo']
        ));
    }
}
