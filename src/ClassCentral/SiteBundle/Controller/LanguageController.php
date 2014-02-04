<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Utility\Breadcrumb;
use ClassCentral\SiteBundle\Utility\PageHeader\PageHeaderFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\Language;
use ClassCentral\SiteBundle\Form\LanguageType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Language controller.
 *
 */
class LanguageController extends Controller
{
    /**
     * Lists all Language entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:Language')->findAll();

        return $this->render('ClassCentralSiteBundle:Language:index.html.twig', array(
            'entities' => $entities
        ));
    }

    /**
     * Finds and displays a Language entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Language')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Language entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Language:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),

        ));
    }

    /**
     * Displays a form to create a new Language entity.
     *
     */
    public function newAction()
    {
        $entity = new Language();
        $form   = $this->createForm(new LanguageType(), $entity);

        return $this->render('ClassCentralSiteBundle:Language:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Creates a new Language entity.
     *
     */
    public function createAction()
    {
        $entity  = new Language();
        $request = $this->getRequest();
        $form    = $this->createForm(new LanguageType(), $entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('language_show', array('id' => $entity->getId())));
            
        }

        return $this->render('ClassCentralSiteBundle:Language:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView()
        ));
    }

    /**
     * Displays a form to edit an existing Language entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Language')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Language entity.');
        }

        $editForm = $this->createForm(new LanguageType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Language:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Language entity.
     *
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Language')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Language entity.');
        }

        $editForm   = $this->createForm(new LanguageType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        $request = $this->getRequest();

        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('language_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:Language:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Language entity.
     *
     */
    public function deleteAction($id)
    {
        $form = $this->createDeleteForm($id);
        $request = $this->getRequest();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ClassCentralSiteBundle:Language')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Language entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('language'));
    }

    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    /**
     * Displays courses by languages
     * @param Request $request
     * @param $slug
     */
    public function viewAction(Request $request, $slug)
    {
        $em = $this->getDoctrine()->getManager();
        $language = $em->getRepository('ClassCentralSiteBundle:Language')->findOneBySlug($slug);

        If(!$language)
        {
            // TODO: Show an error page
            return;
        }

        $cache = $this->get('Cache');
        $filterService = $this->get('Filter');
        $offerings = $cache->get('language_offerings_' . $slug,
            array ($this, 'getOfferingsByLanguage'), array($language));

        // TODO: All Subjects and offerings should be in sync
        $subjects = $cache->get('language_subjects_' . $slug,array($filterService, 'getOfferingSubjects'), array($offerings));
        $pageInfo = PageHeaderFactory::get($language);

        // Set the pageurl for share links
        $pageInfo->setPageUrl(
            $this->container->getParameter('baseurl'). $this->get('router')->generate('lang', array('slug' => $slug))
        );

        $breadcrumbs = array(
            Breadcrumb::getBreadCrumb('Languages',$this->generateUrl('languages')),
            Breadcrumb::getBreadCrumb($language->getName(), $this->generateUrl('lang',array('slug' => $language->getSlug())))
        );
        return $this->render('ClassCentralSiteBundle:Language:view.html.twig',
            array(
                'language' => $language,
                'offerings' => $offerings,
                'page'=>'language',
                'offeringTypes'=> Offering::$types,
                'slug' => $slug,
                'pageInfo' => $pageInfo,
                'offSubjects' => $subjects,
                'listTypes' => UserCourse::$lists,
                'breadcrumbs' => $breadcrumbs
            ));
    }


    public function getOfferingsByLanguage(Language $language)
    {
        $courses = $language->getCourses();
        $courseIds = array();
        foreach($courses as $course)
        {
            $courseIds[] = $course->getId();
        }

        return $this->getDoctrine()->getRepository('ClassCentralSiteBundle:Offering')->findAllByCourseIds($courseIds);
    }

    /**
     * Shows a page which lists all languages
     * @param Request $request
     */
    public function languagesAction(Request $request)
    {
        $cache = $this->get('Cache');
        $languages = $cache->get('language_list_count ', array($this, 'getLanguagesList'),array($this->getDoctrine()->getManager()));
        $breadcrumbs = array(
            Breadcrumb::getBreadCrumb('Languages',$this->generateUrl('languages'))
        );
        return $this->render('ClassCentralSiteBundle:Language:languages.html.twig',array(
                'page' => 'languages',
                'languages' => $languages,
                'breadcrumbs' => $breadcrumbs
            ));
    }

    public function getLanguagesList($em)
    {
        $languagesCount = $em->getRepository('ClassCentralSiteBundle:Language')->getCourseCountByLanguages();
        $allLanguages = $em->getRepository('ClassCentralSiteBundle:Language')->findAll();
        $languages = array();
        foreach($allLanguages as $language)
        {
            if(!isset($languagesCount[$language->getId()]))
            {
                continue; // no count exists. Do not show the language
            }

            $count = $languagesCount[$language->getId()]['courseCount'];
            $language->setCourseCount($count);
            $languages[$language->getId()] = $language;

            $em->detach($language);
        }

        return $languages;
    }


}
