<?php

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Services\Kuber;
use ClassCentral\SiteBundle\Services\UserSession;
use ClassCentral\SiteBundle\Utility\ReviewUtility;
use ClassCentral\SiteBundle\Utility\UniversalHelper;
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
        $userService = $this->get('user_service');


        if(is_numeric($slug))
        {
            $user_id = intval( $slug );
            $user = $em->getRepository('ClassCentralSiteBundle:User')->find( $user_id );
            if($user->getHandle())
            {
                // Redirect the user to the profile url
                $url = $this->get('router')->generate('user_profile_handle', array('slug' => $user->getHandle()));
                return $this->redirect($url,301);
            }
        }
        else
        {
            $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneBy( array('handle'=> $slug) );
        }


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
                'reviews' => $reviews,
                'degrees' => Profile::$degrees,
                'profilePic' => $userService->getProfilePic($user->getId())
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
            'page' => 'edit_profile',
            'degrees' => Profile::$degrees
        ));
    }

    /**
     * Ajax call that takes a
     * @param Request $request
     */
    public function saveAction(Request $request )
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $userService = $this->get('user_service');

        // Get the json post data
        $content = $this->getRequest("request")->getContent();
        if(empty($content))
        {
            return $this->getAjaxResponse(false, "Error retrieving profile details from form");
        }
        $profileData = json_decode($content, true);
        $isAdmin = $this->get('security.context')->isGranted('ROLE_ADMIN');

        $response = $userService->saveProfile( $user, $profileData);

        return UniversalHelper::getAjaxResponse($response);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileImageStep1Action(Request $request)
    {
        $kuber = $this->get('kuber');
        $user = $this->container->get('security.context')->getToken()->getUser();

        // Get the image
        $img = $request->files->get('profile-pic-uploaded');
        if(!$img)
        {
            return UniversalHelper::getAjaxResponse(false,'No image found');
        }

        // Check mime type
        $mimeType = $img->getMimeType();
        if( !in_array($mimeType,array("image/jpeg","image/png")) )
        {
            return UniversalHelper::getAjaxResponse(false,'Only image of type jpeg and png allowed');
        }

        // File size limit check
        $fileSize = $img->getClientSize()/1024;
        // 1 mb limit
        if($fileSize > 1024 )
        {
            return UniversalHelper::getAjaxResponse(false,'Max file size is 1 mb');
        }

        $file = $kuber->upload( $img->getPathname(), Kuber::KUBER_ENTITY_USER,Kuber::KUBER_TYPE_USER_PROFILE_PIC_TMP,$user->getId(),$img->getClientOriginalExtension());

        if($file)
        {
            return UniversalHelper::getAjaxResponse( true, array(
                'imgUrl' => $kuber->getUrlFromFile($file)
            ) );
        }
        else
        {
            return UniversalHelper::getAjaxResponse( false,"Sorry we are having technical difficulties. Please try again later" );
        }

    }

    /**
     * Receives the co-ordinates, crops the image and saves it as
     * profile image
     * @param Request $request
     */
    public function profileImageStep2Action(Request $request)
    {
        $kuber = $this->get('kuber');
        $user = $this->container->get('security.context')->getToken()->getUser();

        $content = $this->getRequest("request")->getContent();
        if(empty($content))
        {
            return UniversalHelper::getAjaxResponse(false, "Crop photo failed. Please try again later");
        }
        $data = json_decode($content, true);

        // Check if the image from step1 exists
        $file = $kuber->getFile( Kuber::KUBER_ENTITY_USER,Kuber::KUBER_TYPE_USER_PROFILE_PIC_TMP,$user->getId() );
        if(!$file)
        {
            return UniversalHelper::getAjaxResponse(false, "Crop photo failed. Please try again later");
        }

        $croppedImage = $this->cropImage($file,$data);
        // Upload the file as profile image
        $newProfilePic = $kuber->upload( $croppedImage, Kuber::KUBER_ENTITY_USER,Kuber::KUBER_TYPE_USER_PROFILE_PIC,$user->getId());
        unlink($croppedImage); // Delete the temporary file

        // Delete the temporary file from S3 and the database
        $kuber->delete( $file );

        // Clear the cache for profile pic
        $this->get('cache')->deleteCache('user_profile_pic_' . $user->getId());

        if(!$newProfilePic)
        {
            return UniversalHelper::getAjaxResponse(false, "Crop photo failed. Please try again later");
        }
        else
        {
            // Set a notification message
            $this->get('user_session')->notifyUser(
                UserSession::FLASH_TYPE_SUCCESS,
                'Profile Photo updated',
                ''
            );



            return UniversalHelper::getAjaxResponse(true);
        }
    }

    /**
     * Crops the image and returns a filepath with the location of a temporary
     * image
     * @param $file
     * @param $coords
     * @return string
     */
    private function cropImage($file, $coords)
    {
        $kuber = $this->get('kuber');

        //Download the file and create a temporary copy of the original
        $imgUrl = $kuber->getUrlFromFile($file);
        $imgFile = '/tmp/'.$file->getFileName();
        file_put_contents($imgFile,file_get_contents($imgUrl));


        $img = new \Imagick($imgFile);
        $img->cropimage(
            $coords['w'],
            $coords['h'],
            $coords['x'],
            $coords['y']
        );
        $croppedFile = '/tmp/crop-'. $file->getFileName();
        $img->writeimage( $croppedFile );

        // Delete the original file
        unlink($imgFile);
        return $croppedFile;
    }
}
