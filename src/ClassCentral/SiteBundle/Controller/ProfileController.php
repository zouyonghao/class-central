<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Utility\ReviewUtility;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\Profile;
use ClassCentral\SiteBundle\Form\ProfileType;

/**
 * Profile controller.
 *
 */
class ProfileController extends Controller
{

    /**
     * Lists all Profile entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:Profile')->findAll();

        return $this->render('ClassCentralSiteBundle:Profile:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Profile entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity  = new Profile();
        $form = $this->createForm(new ProfileType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('profile_show', array('id' => $entity->getId())));
        }

        return $this->render('ClassCentralSiteBundle:Profile:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Profile entity.
     *
     */
    public function newAction()
    {
        $entity = new Profile();
        $form   = $this->createForm(new ProfileType(), $entity);

        return $this->render('ClassCentralSiteBundle:Profile:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Profile entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Profile')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Profile entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Profile:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),        ));
    }

    /**
     * Displays a form to edit an existing Profile entity.
     *
     */
    public function editAdminAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Profile')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Profile entity.');
        }

        $editForm = $this->createForm(new ProfileType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:Profile:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing Profile entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:Profile')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Profile entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new ProfileType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('profile_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:Profile:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Profile entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ClassCentralSiteBundle:Profile')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Profile entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('profile'));
    }

    /**
     * Creates a form to delete a Profile entity by id.
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
     *
     * @param $slug user id or username
     */
    public function profileAction(Request $request,$slug)
    {
        $em = $this->getDoctrine()->getManager();
        $cl = $this->get('course_listing');

        $user_id = intval( $slug );

        $user = $em->getRepository('ClassCentralSiteBundle:User')->find( $user_id );
        if(!$user)
        {
            // User not found
            throw new \Exception("User $slug not found");
        }
        $profile = ($user->getProfile()) ? $user->getProfile() : new Profile();

        // Get users course listing. This is the same function on My Courses page
        // and contains additional information related to pagination
        $clDetails = $cl->userLibrary( $user, $request);

        $reviews = array();
        foreach($user->getReviews() as $review)
        {
            $r = ReviewUtility::getReviewArray($review);
            $reviews[$r['course']['id']] = $r;
        }

        return $this->render('ClassCentralSiteBundle:Profile:profile.html.twig', array(
                'user' => $user,
                'profile'=> $profile,
                'listTypes' => UserCourse::$transcriptList,
                'coursesByLists' => $clDetails['coursesByLists'],
                'reviews' => $reviews
            )
        );

    }

    /**
     * Renders a page for the user  show the edit their profile
     * Note: The firewall takes care of whether the user is logged in
     * @param Request $request
     */
    public function editAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        return $this->render('ClassCentralSiteBundle:Profile:profile.edit.html.twig',array(
            'page' => 'edit_profile'
        ));
    }

    /**
     * Ajax call that takes a
     * @param Request $request
     */
    public function saveProfileAction(Request $request )
    {
        $user = $this->container->get('security.context')->getToken()->getUser();

        // Get the json post data
        $content = $this->getRequest("request")->getContent();
        if(empty($content))
        {
            return $this->getAjaxResponse(false, "Error retrieving profile details from form");
        }
        $profileData = json_decode($content, true);
        $isAdmin = $this->get('security.context')->isGranted('ROLE_ADMIN');
    }

}
