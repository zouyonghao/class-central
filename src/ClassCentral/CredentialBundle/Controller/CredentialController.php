<?php

namespace ClassCentral\CredentialBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\CredentialBundle\Entity\Credential;
use ClassCentral\CredentialBundle\Form\CredentialType;

/**
 * Credential controller.
 *
 */
class CredentialController extends Controller
{

    /**
     * Lists all Credential entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralCredentialBundle:Credential')->findAll();

        return $this->render('ClassCentralCredentialBundle:Credential:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Credential entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity  = new Credential();
        $form = $this->createForm(new CredentialType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('credential_show', array('id' => $entity->getId())));
        }

        return $this->render('ClassCentralCredentialBundle:Credential:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Credential entity.
     *
     */
    public function newAction()
    {
        $entity = new Credential();
        $form   = $this->createForm(new CredentialType(), $entity);

        return $this->render('ClassCentralCredentialBundle:Credential:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Credential entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralCredentialBundle:Credential')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Credential entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralCredentialBundle:Credential:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),        ));
    }

    /**
     * Displays a form to edit an existing Credential entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralCredentialBundle:Credential')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Credential entity.');
        }

        $editForm = $this->createForm(new CredentialType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralCredentialBundle:Credential:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Credential entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralCredentialBundle:Credential')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Credential entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new CredentialType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('credential_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralCredentialBundle:Credential:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Credential entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ClassCentralCredentialBundle:Credential')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Credential entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('credential'));
    }

    /**
     * Creates a form to delete a Credential entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }

    /**
     * Displays a particular credential
     * @param Request $request
     * @param $slug
     */
    public function credentialAction(Request $request, $slug)
    {
        // Get the credential
        $esCredentials = $this->get('es_credentials');


        $result = $esCredentials->findBySlug($slug);
        if( $result['hits']['total'] != 1 )
        {
            // Error
            return;
        }

        return $this->render('ClassCentralCredentialBundle:Credential:credential.html.twig', array(
                'page' => 'credential',
                'credential' => $result['hits']['hits'][0]['_source']
        ));
    }

    /**
     * @param Request $request
     */
    public function credentialsAction(Request $request)
    {
        $esCredentials = $this->get('es_credentials');


        return $this->render('ClassCentralCredentialBundle:Credential:credentials.html.twig', array(
                'page' => 'credentials',
                'credentials' => $esCredentials->find()
        ));
    }
}
