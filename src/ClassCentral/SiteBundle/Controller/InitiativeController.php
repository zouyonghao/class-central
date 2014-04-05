<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Utility\PageHeader\PageHeaderFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Form\InitiativeType;

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
    public function providerAction($slug)
    {
        $cache = $this->get('cache');
        $filter =$this->get('filter');
        $es = $this->get('es_client'); // elastic search
        $indexName = $this->container->getParameter('es_index_name');

        // Get the provider
        $provider = $this->getDoctrine()->getManager()
                    ->getRepository('ClassCentralSiteBundle:Initiative')->findOneBy( array('code'=>$slug ) );
        $pageInfo =  PageHeaderFactory::get($provider);

        $params['index'] = $indexName;
        $params['type'] = 'course';
        $params['body']['size'] = 1000;


        $query = array(
            'match' => array(
                'provider.code' => $slug,
            )
        );

        $sort = array(
            "nextSession.state" => array(
                "order" => "desc"
            ),
            "nextSession.startDate" => array(
                "order" => "asc"
            )
        );
        $facets = array(
            "subjects" => array(
                'terms' => array(
                    'field' => 'subjects.id',
                    'size' => 40
                )
            ),
            "language" => array(
                'terms' => array(
                    'field' => 'language.id',
                    'size' => 40
                )
            )
        );

        $params['body']['sort'] = $sort;
        $params['body']['query'] = $query;
        $params['body']['facets'] = $facets;

        $results = $es->search($params);
        $subjectIds = array();
        foreach($results['facets']['subjects']['terms'] as $term)
        {
            $subjectIds[] = $term['term'];
        }
        $allSubjects = $filter->getCourseSubjects($subjectIds);

        $languageIds = array();
        foreach($results['facets']['language']['terms'] as $term)
        {
            $languageIds[] = $term['term'];
        }
        $allLanguages = $filter->getCourseLanguages($languageIds);


        return $this->render('ClassCentralSiteBundle:Initiative:provider.html.twig',array(
            'results' => $results,
            'listTypes' => UserCourse::$lists,
            'allSubjects' => $allSubjects,
            'allLanguages' => $allLanguages,
            'page' => 'initiative',
            'provider' => $provider,
            'pageInfo' => $pageInfo
        ));
    }
}
