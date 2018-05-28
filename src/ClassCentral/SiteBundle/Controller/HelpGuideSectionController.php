<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Utility\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\HelpGuideSection;
use ClassCentral\SiteBundle\Form\HelpGuideSectionType;

/**
 * HelpGuideSection controller.
 *
 */
class HelpGuideSectionController extends Controller
{

    /**
     * Lists all HelpGuideSection entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:HelpGuideSection')->findAll();

        return $this->render('ClassCentralSiteBundle:HelpGuideSection:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new HelpGuideSection entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new HelpGuideSection();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('help_guides_admin_sections'));
        }

        return $this->render('ClassCentralSiteBundle:HelpGuideSection:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a HelpGuideSection entity.
     *
     * @param HelpGuideSection $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(HelpGuideSection $entity)
    {
        $form = $this->createForm(new HelpGuideSectionType(), $entity, array(
            'action' => $this->generateUrl('help-guide-section_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new HelpGuideSection entity.
     *
     */
    public function newAction()
    {
        $entity = new HelpGuideSection();
        $form   = $this->createCreateForm($entity);

        $adminBreadCrumbs = [];
        $adminBreadCrumbs[] = Breadcrumb::helpGuidesTopLevelBreadCrumb($this);
        $adminBreadCrumbs[] = Breadcrumb::getBreadCrumb('Section', $this->generateUrl('help_guides_admin_sections'));
        $adminBreadCrumbs[] = Breadcrumb::getBreadCrumb('New');


        return $this->render('ClassCentralSiteBundle:HelpGuideSection:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'adminBreadCrumbs' => $adminBreadCrumbs
        ));
    }

    /**
     * Finds and displays a HelpGuideSection entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:HelpGuideSection')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HelpGuideSection entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:HelpGuideSection:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing HelpGuideSection entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:HelpGuideSection')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HelpGuideSection entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        $adminBreadCrumbs = [];
        $adminBreadCrumbs[] = Breadcrumb::helpGuidesTopLevelBreadCrumb($this);
        $adminBreadCrumbs[] = Breadcrumb::getBreadCrumb('Section', $this->generateUrl('help_guides_admin_sections'));
        $adminBreadCrumbs[] = Breadcrumb::getBreadCrumb('Edit');

        return $this->render('ClassCentralSiteBundle:HelpGuideSection:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'adminBreadCrumbs' => $adminBreadCrumbs
        ));
    }

    /**
    * Creates a form to edit a HelpGuideSection entity.
    *
    * @param HelpGuideSection $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(HelpGuideSection $entity)
    {
        $form = $this->createForm(new HelpGuideSectionType(), $entity, array(
            'action' => $this->generateUrl('help-guide-section_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing HelpGuideSection entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:HelpGuideSection')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HelpGuideSection entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $this->get('cache')->deleteCache('help_guides_section_articles_' . $entity->getSlug());
            $em->flush();

            return $this->redirect($this->generateUrl('help-guide-section_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:HelpGuideSection:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a HelpGuideSection entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ClassCentralSiteBundle:HelpGuideSection')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find HelpGuideSection entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('help-guide-section'));
    }

    /**
     * Creates a form to delete a HelpGuideSection entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('help-guide-section_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
