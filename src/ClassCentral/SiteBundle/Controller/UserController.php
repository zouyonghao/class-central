<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\MoocTrackerCourse;
use ClassCentral\SiteBundle\Entity\MoocTrackerSearchTerm;
use ClassCentral\SiteBundle\Form\SignupType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Form\UserType;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;



/**
 * User controller.
 *
 */
class UserController extends Controller
{

    /**
     * Lists all User entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:User')->findAll();

        return $this->render('ClassCentralSiteBundle:User:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new User entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity  = new User();
        $form = $this->createForm(new UserType(), $entity);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('user_show', array('id' => $entity->getId())));
        }

        return $this->render('ClassCentralSiteBundle:User:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new User entity.
     *
     */
    public function newAction()
    {
        $entity = new User();
        $form   = $this->createForm(new UserType(), $entity);

        return $this->render('ClassCentralSiteBundle:User:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a User entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:User:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),        ));
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createForm(new UserType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ClassCentralSiteBundle:User:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Edits an existing User entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ClassCentralSiteBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new UserType(), $entity);
        $editForm->bind($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('user_edit', array('id' => $id)));
        }

        return $this->render('ClassCentralSiteBundle:User:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a User entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->bind($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ClassCentralSiteBundle:User')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find User entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('user'));
    }

    /**
     * Creates a form to delete a User entity by id.
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
     * Shows the signup form
     */
    public function signUpAction($form = null)
    {
        // Redirect user if already logged in
        if($this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            return $this->redirect($this->generateUrl('ClassCentralSiteBundle_homepage'));
        }

        if(!$form)
        {
            $form   = $this->createForm(new SignupType(), new User(),array(
                'action' => $this->generateUrl('signup_create_user')
            ));
        }

        return $this->render('ClassCentralSiteBundle:User:signup.html.twig', array(
            'page' => 'signup',
            'form' => $form->createView()
        ));
    }

    /**
     * Saves the course id in session before redirecting the user to signup page
     * @param Request $request
     * @param $courseId
     */
    public function signUpMoocAction(Request $request, $courseId)
    {
        $this->get('user_session')->saveSignupReferralDetails(array('mooc' => $courseId));
        return $this->redirect($this->generateUrl('signup'));
    }

    /**
     * Saves the search term in session before redirecting the user to signup page
     * @param Request $request
     * @param $searchTerm
     */
    public function signUpSearchTermAction(Request $request, $searchTerm)
    {
        $this->get('user_session')->saveSignupReferralDetails(array('searchTerm' => $searchTerm));
        return $this->redirect($this->generateUrl('signup'));
    }

    /**
     * Create and save the user
     * @param Request $request
     */
    public function createUserAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $form   = $this->createForm(new SignupType(), new User(),array(
            'action' => $this->generateUrl('signup_create_user')
        ));
        $form->handleRequest($request);

        if($form->isValid())
        {
            $user = $form->getData();
            $user->setEmail(strtolower($user->getEmail())); // Normalize the email
            $password = $user->getPassword();
            $user->setPassword(password_hash($password,PASSWORD_BCRYPT,array("cost" => 10)));
            $em->persist($user);
            $em->flush();

            // Login the user
            $token = new UsernamePasswordToken($user, $password,'secured_area',$user->getRoles());
            $this->get('security.context')->setToken($token);

            // Check where the user reached the signed in page
            $userSession = $this->get('user_session');
            $referralDetails = $userSession->getSignupReferralDetails();
            if(!empty($referralDetails))
            {
                if(array_key_exists('mooc',$referralDetails))
                {
                    $this->saveCourseInMoocTracker($user,$referralDetails['mooc']);
                }
                else if (array_key_exists('searchTerm',$referralDetails))
                {
                    $this->saveSearchTermInMoocTracker($user,$referralDetails['searchTerm']);
                }

                $userSession->clearSignupReferralDetails();
            }

            return $this->redirect($this->generateUrl('mooctracker'));
        }

        // Form is not valid
        return $this->signUpAction($form);
    }


    /**
     * Add course to MOOC tracker
     */
    public function addCourseToMOOCTrackerAction(Request $request, $courseId)
    {
        // Check if the user is logged in
        // Firewall should take care of this

        // Save the course in MOOC tracker
        $course = $this->saveCourseInMoocTracker(
            $this->get('security.context')->getToken()->getUser(),
            $courseId);
        if(!$course)
        {
            // invalid course
            //TODO: Return error
            return;
        }

        // redirect the user to course page
        return $this->redirect($this->generateUrl('ClassCentralSiteBundle_mooc',array(
            'id' => $courseId,
            'slug' => $course->getSlug()
        )));


    }

    /**
     * Saves the course in MOOC tracker
     * @param $user
     * @param $courseId
     */
    private function saveCourseInMoocTracker($user, $courseId)
    {
        $userSession = $this->get('user_session');
        $em = $this->getDoctrine()->getManager();
        $course = $em->getRepository('ClassCentralSiteBundle:Course')->find($courseId);
        if(!$course)
        {

            return false;
        }

        // Check if the user is already tracking this course
        // TODO: Do a db check
        if (!$userSession->isCourseAddedToMT($courseId))
        {
            // Add the course to MOOC tracker
            $moocTrackerCourse = new MoocTrackerCourse();
            $moocTrackerCourse->setUser($user);
            $moocTrackerCourse->setCourse($course);
            $em->persist($moocTrackerCourse);
            $em->flush();

            $userSession->saveUserInformationInSession();
        }

        return $course;
    }

    /**
     * Add search term to MOOC Tracker
     * @param Request $request
     * @param $id
     */
    public function addSearchTermToMOOCTrackerAction(Request $request, $searchTerm)
    {
        // Check if the user is logged in
        // Firewall should take care of this

        // TODO: Validate the search term

        $user = $this->get('security.context')->getToken()->getUser();
        $this->saveSearchTermInMoocTracker($user,$searchTerm);

        return $this->redirect($this->generateUrl('ClassCentralSiteBundle_search',array(
            'q' => $searchTerm
        )));

    }

    private function saveSearchTermInMoocTracker($user,$searchTerm)
    {
        $userSession = $this->get('user_session');
        $em = $this->getDoctrine()->getManager();

        if(!$userSession->isSearchTermAddedToMT($searchTerm))
        {
            $mtSearchTerm = new MoocTrackerSearchTerm();
            $mtSearchTerm->setUser($user);
            $mtSearchTerm->setSearchTerm($searchTerm);
            $em->persist($mtSearchTerm);
            // Add the searchterm to user
            $user->addMoocTrackerSearchTerm($mtSearchTerm);
            $em->flush();

            $userSession->saveUserInformationInSession();
        }
    }

    /***
     * For logged in users renders their mooc tracker page
     * For logged out users renders the signup page
     * @param Request $request
     */
    public function moocTrackerAction(Request $request)
    {
        // Redirect user if already logged in
        if($this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            return $this->moocTrackerProfilePage($request);
        }
        else
        {
            return $this->signUpAction();
        }

    }

    public function moocTrackerProfilePage(Request $request)
    {
        $userSession = $this->get('user_session');

        // Search Terms
        $searchTerms = $userSession->getMTSearchTerms();

        // Courses
        $courseIds = $userSession->getMTCourses();
        $user = $this->get('security.context')->getToken()->getUser();
        $courses = array();
        foreach($user->getMoocTrackerCourses() as $moocTrackerCourse)
        {
            $courses[] = $moocTrackerCourse->getCourse();
        }

        return $this->render('ClassCentralSiteBundle:User:mooc-tracker-user.html.twig', array(
            'page' => 'mooc-tracker',
            'searchTerms' => $searchTerms,
            'courses' => $courses
        ));


    }

}
