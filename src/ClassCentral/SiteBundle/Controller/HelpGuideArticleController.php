<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Utility\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\HelpGuideArticle;
use ClassCentral\SiteBundle\Form\HelpGuideArticleType;

/**
 * HelpGuideArticle controller.
 *
 */
class HelpGuideArticleController extends Controller
{

    /**
     * Lists all HelpGuideArticle entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:HelpGuideArticle')->findAll();

        return $this->render('ClassCentralSiteBundle:HelpGuideArticle:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new HelpGuideArticle entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new HelpGuideArticle();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('help-guide-article_edit', array('id' => $entity->getId())));
        }

        return $this->render('ClassCentralSiteBundle:HelpGuideArticle:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a HelpGuideArticle entity.
     *
     * @param HelpGuideArticle $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(HelpGuideArticle $entity)
    {
        $form = $this->createForm(new HelpGuideArticleType($this->container->getParameter('help_guides_writers')), $entity, array(
            'action' => $this->generateUrl('help-guide-article_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new HelpGuideArticle entity.
     *
     */
    public function newAction()
    {
        $entity = new HelpGuideArticle();
        $form   = $this->createCreateForm($entity);

        $adminBreadCrumbs = [];
        $adminBreadCrumbs[] = Breadcrumb::helpGuidesTopLevelBreadCrumb($this);
        $adminBreadCrumbs[] = Breadcrumb::getBreadCrumb('Article',$this->generateUrl('help_guides_admin_article_index'));

        $adminBreadCrumbs[] = Breadcrumb::getBreadCrumb('New');

        return $this->render('ClassCentralSiteBundle:HelpGuideArticle:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'adminBreadCrumbs' => $adminBreadCrumbs
        ));
    }

    /**
     * Finds and displays a HelpGuideArticle entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:HelpGuideArticle')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HelpGuideArticle entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:HelpGuideArticle:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing HelpGuideArticle entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:HelpGuideArticle')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HelpGuideArticle entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        $adminBreadCrumbs = [];
        $adminBreadCrumbs[] = Breadcrumb::helpGuidesTopLevelBreadCrumb($this);

        $adminBreadCrumbs[] = Breadcrumb::getBreadCrumb('Edit');

        return $this->render('ClassCentralSiteBundle:HelpGuideArticle:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'adminBreadCrumbs' => $adminBreadCrumbs
        ));
    }

    /**
    * Creates a form to edit a HelpGuideArticle entity.
    *
    * @param HelpGuideArticle $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(HelpGuideArticle $entity)
    {
        $form = $this->createForm(new HelpGuideArticleType($this->container->getParameter('help_guides_writers')), $entity, array(
            'action' => $this->generateUrl('help-guide-article_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing HelpGuideArticle entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:HelpGuideArticle')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find HelpGuideArticle entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $this->get('cache')->deleteCache('help_guides_article_' . $entity->getSlug());
            $em->flush();

            return $this->redirect($this->generateUrl('help-guide-article_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:HelpGuideArticle:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a HelpGuideArticle entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ClassCentralSiteBundle:HelpGuideArticle')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find HelpGuideArticle entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('help-guide-article'));
    }

    /**
     * Creates a form to delete a HelpGuideArticle entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('help-guide-article_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }

}
