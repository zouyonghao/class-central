<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dhawal
 * Date: 6/13/13
 * Time: 9:11 PM
 * To change this template use File | Settings | File Templates.
 */

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserPreference;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AdminController extends Controller {

    public function indexAction()
    {
        return $this->render('ClassCentralSiteBundle:Admin:index.html.twig');
    }

    public function deleteUserAction(Request $request, $userId)
    {
        $msg = '';
        $toBeDeleted = false;
        $userService = $this->container->get('user_service');

        $user = $this->getDoctrine()->getManager()->getRepository('ClassCentralSiteBundle:User')->find($userId);
        if($user)
        {
            $userPrefs = $user->getUserPreferencesByTypeMap();
            if( isset($userPrefs[ UserPreference::USER_PROFILE_DELETE_ACCOUNT] ))
            {
                $msg = 'The user has been marked for deletion';
                $toBeDeleted = true;
            }

            if($request->isMethod('POST'))
            {
                $postFields = $request->request->all();
                if(!$toBeDeleted && isset($postFields['delete-user']))
                {

                    // Check if user is not an admin user
                    if ($user->getRole() != 'ROLE_ADMIN')
                    {
                        // Delete the user
                        $userService->updatePreference(
                            $user,
                            UserPreference::USER_PROFILE_DELETE_ACCOUNT,
                            json_encode(array(
                                'user_id' => $user->getId(),
                            ))
                        );
                        $msg = 'The user has been marked for deletion';
                        $toBeDeleted = true;
                    }
                    else
                    {
                        $msg = 'Admin users cannot be deleted';
                    }
                }

                if($toBeDeleted && isset($postFields['do-not-delete-user']))
                {
                    // Do not delete users
                    $userService->deletePreference(
                        $user,
                        UserPreference::USER_PROFILE_DELETE_ACCOUNT
                    );
                    $msg = 'The user account will not be deleted';
                    $toBeDeleted = false;
                }
            }
        }
        else
        {
            $msg = 'User account does not exist';
        }


        return $this->render('ClassCentralSiteBundle:Admin:delete_user.html.twig',[
            'msg' => $msg,
            'user' => $user,
            'toBeDeleted' => $toBeDeleted
        ]);
    }

    /**
     * @param Request $request
     */
    public function findUserByEmailAction(Request $request)
    {
        $msg = '';
        $user = null;
        if($request->isMethod('POST'))
        {
            $postFields = $request->request->all();
            $email = $postFields['email'];
            if(is_numeric($email))
            {
                // Find user by id
                $user = $this->getDoctrine()->getManager()->getRepository('ClassCentralSiteBundle:User')->find($email);
            }
            else
            {
                $user = $this->getDoctrine()->getManager()->getRepository('ClassCentralSiteBundle:User')->findOneByEmail($email);
            }
            if(!$user)
            {
                $msg = "User with email address $email does not have a Class Central account";
            }
        }


        return $this->render('ClassCentralSiteBundle:Admin:find_user_by_email.html.twig',[
            'msg' => $msg,
            'user' => $user,
            'userSignupType' => [
                User::SIGNUP_TYPE_FORM => 'Signup Form',
                User::SIGNUP_TYPE_FACEBOOK => "Facebook",
                User::SIGNUP_TYPE_GOOGLE => "Google"
             ]
        ]);
    }
}