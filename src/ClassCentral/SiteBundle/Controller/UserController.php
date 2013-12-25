<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\MoocTrackerCourse;
use ClassCentral\SiteBundle\Entity\MoocTrackerSearchTerm;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Entity\VerificationToken;
use ClassCentral\SiteBundle\Form\SignupType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Form\UserType;
use Symfony\Component\HttpFoundation\Response;


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
            return $this->redirect($this->generateUrl('mooctracker'));
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
        $userService = $this->get('user_service');
        $userSession = $this->get('user_session');
        $em = $this->getDoctrine()->getManager();
        $newsletter = $em->getRepository('ClassCentralSiteBundle:Newsletter')->findOneByCode("mooc-report");

        $form   = $this->createForm(new SignupType(), new User(),array(
            'action' => $this->generateUrl('signup_create_user')
        ));
        $form->handleRequest($request);

        if($form->isValid())
        {
            $user = $form->getData();
            $user = $userService->signup($user, true); // true - verification email

            // Normal flow. Subscribe the user to a mooc report newsletter
            if($newsletter)
            {
                // Save the user preferences
                $user->subscribe($newsletter);
                $em->persist($user);
                $em->flush();
            }

            // Check where the user reached the signed in page
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

    public function forgotPasswordAction(Request $request)
    {
        // Redirect user if already logged in
        if($this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            return $this->redirect($this->generateUrl('mooctracker'));
        }

        return $this->render('ClassCentralSiteBundle:User:forgotPassword.html.twig');
    }

    public function forgotPasswordSendEmailAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $tokenService = $this->get('verification_token');
        $mailgun = $this->get('mailgun');
        $templating = $this->get('templating');
        $session = $this->get('session');
        $logger = $this->get('logger');

        $email = $request->request->get('email');
        if($email)
        {
            $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneByEmail($email);
            if($user)
            {
                $token = $tokenService->create("forgot_password=1&user_id=" . $user->getId(), VerificationToken::EXPIRY_1_DAY);
                if ($this->container->getParameter('kernel.environment') != 'test')
                {
                    $html = $templating->renderResponse('ClassCentralSiteBundle:Mail:forgotpassword.html.twig', array('token' => $token->getToken()))->getContent();
                    $mailgunResponse = $mailgun->sendSimpleText($user->getEmail(),"no-reply@class-central.com","Reset your Class Central password",$html);
                    if(!isset($mailgunResponse['id']))
                    {
                        $logger->error('Error sending reset password', array('user_id'=>$user->getId(),'mailgun_response' => $mailgunResponse));
                    }
                    else
                    {
                        $logger->info('Reset password sent mail sent', array('user_id'=>$user->getId(),'mailgun_response' => $mailgunResponse));
                    }
                }
            }

        }

        $session->set('fpSendEmail',true);
        return $this->redirect($this->generateUrl('forgotpassword'));
    }

    public function resetPasswordAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();
        $tokenService = $this->get('verification_token');
        $session = $this->get('session');

        $tokenEntity = $tokenService->get($token);
        $tokenValid = false;
        if($tokenEntity)
        {
            parse_str($tokenEntity->getValue(), $tokenValue);
            if(isset($tokenValue['forgot_password']))
            {
                $session->set('reset_user_id', $tokenValue['user_id']); // Save the user_id in session
                $tokenService->delete($tokenEntity); // delete the token since its one time use
                $tokenValid = true;
            }
        }

        return $this->render('ClassCentralSiteBundle:User:resetPassword.html.twig',array('tokenValid' => $tokenValid));
    }


    public function resetPasswordSaveAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $this->get('session');
        $userId = $session->get('reset_user_id');
        $password = $request->request->get('password');
        $token = 'failed';
        if(!empty($password) && !empty($userId) )
        {
            $user = $em->getRepository('ClassCentralSiteBundle:User')->find($userId);
            if($user)
            {
                // Reset password
                $user->setPassword($user->getHashedPassword($password));
                $em->persist($user);
                $em->flush();

                $token = 'succeeded';
                $session->set('fpResetPassword',true);
                $session->remove('reset_user_id');
            }
        }


        return $this->redirect($this->generateUrl('resetPassword', array('token' => $token)));
    }

    /**
     * Verifies the email
     * @param Request $request
     * @param $token
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function verifyEmailAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();
        $verifyTokenService = $this->get('verification_token');
        $newsletterService = $this->get('newsletter');
        $logger = $this->get('logger');

        $tokenEntity = $verifyTokenService->get($token);
        $tokenValid = false;
        if($tokenEntity)
        {
            $tokenValue = $tokenEntity->getTokenValueArray();
            if($tokenValue['verify'] && $tokenValue['email'])
            {
                $email = $tokenValue['email'];
                $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneByEmail($email);
                if($user)
                {
                    $user->setIsverified(1);
                    $em->persist($user);
                    $em->flush();

                    $tokenValid = true;

                    // Subscribe the user to different mailing lists
                    foreach($user->getNewsletters() as $newsletter)
                    {
                        if ($this->container->getParameter('kernel.environment') != 'test')
                        {
                            $subscribed = $newsletterService->subscribeUser($newsletter, $user);
                            $logger->info("verifyEmailAction : user newsletter subscription", array(
                                    'user' => $user->getId(),
                                    'newsletter' => $newsletter->getCode(),
                                    'subscribed' => $subscribed
                             ));
                        }
                    }
                }

                $emailEntity = $em->getRepository('ClassCentralSiteBundle:Email')->findOneByEmail($email);
                if($emailEntity)
                {
                    $emailEntity->setIsverified(1);
                    $em->persist($emailEntity);
                    $em->flush();

                    $tokenValid = true;

                    // Subscribe the user to different mailing lists
                    foreach($emailEntity->getNewsletters() as $newsletter)
                    {
                        if ($this->container->getParameter('kernel.environment') != 'test')
                        {
                            $subscribed = $newsletterService->subscribeEmail($newsletter, $emailEntity);
                            $logger->info("verifyEmailAction : email newsletter subscription", array(
                                    'email' => $emailEntity->getId(),
                                    'newsletter' => $newsletter->getCode(),
                                    'subscribed' => $subscribed
                                ));
                        }
                    }
                }

                $verifyTokenService->delete($tokenEntity);
            }
        }

        return $this->render('ClassCentralSiteBundle:User:verifyEmail.html.twig',array(
                'tokenValid' => $tokenValid
        ));
    }

    /**
     * Ajax call to add a course to the user profile
     * @param Request $request
     */
    public function addCourseAction(Request $request)
    {
        return $this->addRemoveCourse($request, 'add');
    }

    /**
     * Removes the course from a users library
     * @param Request $request
     */
    public function removeCourseAction(Request $request)
    {
        return $this->addRemoveCourse($request, 'remove');
    }

    private function addRemoveCourse(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();
        $userService = $this->get('user_service');

        $user = $this->get('security.context')->getToken()->getUser();
        if(!$user)
        {
            // No logged in user
            return $this->getAjaxResponse(false, "User is not logged in");
        }
        try
        {
            // Parse the request parameters
            $params = $this->getCourseListingCallRequestParams($request);

            // Get the course
            $course = $em->find('ClassCentralSiteBundle:Course',$params['courseId']);
            if(!$course)
            {
                return $this->getAjaxResponse(false, "Course does not exist");
            }

            if($type == 'add') // Add a course
            {
                $uc = $userService->addCourse($user, $course, $params['listId']);

                if($uc)
                {
                    return $this->getAjaxResponse(true);
                }

                return $this->getAjaxResponse(false, "Course already added");

            }
            else if ($type == 'remove') // Remove a course
            {
                $result = $userService->removeCourse($user, $course, $params['listId']);
                if($result)
                {
                    return $this->getAjaxResponse(true);
                }
                else
                {
                    return $this->getAjaxResponse(false,"Course wasnt added, so cant be removed");
                }
            }
            else {
                return $this->getAjaxResponse(false);
            }

        }
        catch (\Exception $e)
        {
            // TODO: Log the exception
            return $this->getAjaxResponse(false, "Exception : " . $e->getMessage());
        }
    }

    private function getAjaxResponse($success = false, $message = '')
    {
        $response = array('success' => $success, 'message' => $message);
        return new Response(json_encode($response));
    }

    private function getCourseListingCallRequestParams(Request $request)
    {

        $courseId = $request->query->get('c_id');
        if(empty($courseId))
        {
            throw new \Exception("Course Id is missing");
        }
        $listId = $request->query->get('l_id');
        if(!array_key_exists($listId,UserCourse::$lists))
        {
            throw new \Exception("Invalid List Id");
        }

        $params =  array(
            'courseId' => $courseId,
            'offeringId'=> $request->query->get('o_id'),
            'listId' => $listId
        );

        return $params;
    }

    /**
     * Shows user their library of courses
     * @param Response $response
     */
    public function libraryAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $userSession = $this->get('user_session');
        $offeringRepo = $em->getRepository('ClassCentralSiteBundle:Offering');
        $filterService = $this->get('Filter');

        // Check if user is already logged in.
        if(!$this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            return $this->redirect($this->generateUrl('login'));
        }
        $user = $this->get('security.context')->getToken()->getUser();

        // Get all the courses arrays and categorize by list type
        $offerings = array();

        // Initialize the offering arrays for different list type
        foreach(UserCourse::getListTypes() as $list )
        {
            $offerings[$list] = array();
        }

        foreach($user->getUserCourses() as $userCourse)
        {
            $list = $userCourse->getList();
            $offering = $offeringRepo->getOfferingArray($userCourse->getCourse()->getNextOffering());
            $offerings[$list['slug']][] = $offering;
        }

        $lang = $filterService->getOfferingLanguages($offerings);
        $subjects = $filterService->getOfferingSubjects($offerings);

        return $this->render('ClassCentralSiteBundle:User:library.html.twig', array(
                'page' => 'user-library',
                'offerings' => $offerings,
                'listTypes' => UserCourse::$lists,
                'offLanguages' => $lang,
                'offSubjects' => $subjects
        ));


    }

}
