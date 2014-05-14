<?php

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\Spotlight;
use ClassCentral\SiteBundle\Form\SpotlightType;

/**
 * Spotlight controller.
 *
 */
class SpotlightController extends Controller
{

    /**
     * Lists all Spotlight entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:Spotlight')->findAll();

        return $this->render('ClassCentralSiteBundle:Spotlight:index.html.twig', array(
            'entities' => $entities,
        ));
    }

    /**
     * Finds and displays a Spotlight entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Spotlight')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Spotlight entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Spotlight:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),        ));
    }

    /**
     * Displays a form to edit an existing Spotlight entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Spotlight')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Spotlight entity.');
        }

        $editForm = $this->createForm(new SpotlightType(), $entity);

        return $this->render('ClassCentralSiteBundle:Spotlight:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

    /**
     * Edits an existing Spotlight entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Spotlight')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Spotlight entity.');
        }

        $editForm = $this->createForm(new SpotlightType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            // Flush the cache
            $cache = $this->get('Cache');
            $cache->deleteCache ('spotlight_cache');

            return $this->redirect($this->generateUrl('spotlight_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:Spotlight:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ));
    }

}
