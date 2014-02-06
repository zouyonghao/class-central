<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dhawal
 * Date: 9/16/13
 * Time: 11:41 PM
 * To change this template use File | Settings | File Templates.
 */

namespace ClassCentral\SiteBundle\Controller;

use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserFb;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session;

class LoginController extends Controller{

    public function loginAction(Request $request)
    {
        // Check if user is not already logged in.
        if($this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            return $this->redirect($this->generateUrl('ClassCentralSiteBundle_homepage'));
        }


        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR))
        {
            $error = $request->attributes->get(
                SecurityContext::AUTHENTICATION_ERROR
            );
        }
        else
        {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }


        return $this->render(
            'ClassCentralSiteBundle:Login:login.html.twig',
            array(
                // last username entered by the user
                'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                'error'         => $error,
            )
        );
    }

    /**
     * Redirects the user to fb auth url
     * @param Request $request
     */
    public function redirectToAuthorizationAction(Request $request)
    {
        $facebook = $this->createFacebookObj();
        $redirectUrl = $this->generateUrl(
            'fb_authorize_redirect',
            array(),
            true
        );

        $url = $facebook->getLoginUrl(array(
            'redirect_uri' => $redirectUrl,
            'scope' => array('email')
        ));

        return $this->redirect($url);
    }

    public function fbReceiveAuthorizationCodeAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $fb = $this->createFacebookObj();
        $userService = $this->get('user_service');
        $userSession = $this->get('user_session');
        $logger = $this->get('logger');

        $logger->info("FBAUTH: FB auth redirect");

        $userId = $fb->getUser();
        if(!$userId)
        {
            // Redirect to the signup page
            $logger->info("FBAUTH: FB auth denied by the user");
            return $this->redirect($this->generateUrl('signup'));
        }

        try {
            $fbUser = $fb->api('/me');
            $email = strtolower($fbUser['email']);
            if(!$email)
            {
                // TODO : Render error page
                $logger->error("FBAUTH: Email missing");
                return null;

            }
            $name = $fbUser['name'];

            // Check if the user exists
            $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneBy(array(
                'email' => $email
            ));

            if($user)
            {

               $userService->login($user);
               // Check whether the user has fb details
               $ufb = $user->getFb();
               if($ufb)
               {
                   $logger->info("FBAUTH: FB user exists");
                   // Update the token
                   $ufb->setAccessToken($fb->getAccessToken());
               }
               else
               {
                   $logger->info("FBAUTH: Email exists but UserFb table is empty");
                   // Create a FB info
                   $ufb = new UserFb();
                   $ufb->setFbEmail($email);
                   $ufb->setFbId($userId);
                   $ufb->setUserInfo(json_encode($fbUser));
                   $ufb->setAccessToken($fb->getAccessToken());
                   $ufb->setUser($user);

               }

               $em->persist($ufb);
               $em->flush();

               $userSession->login($user);
               return $this->redirect( $this->generateUrl('user_library') );
            }
            else
            {
                $logger->info("FBAUTH: New user");
                // Create a new account
                $user = new User();
                $user->setEmail($email);
                $user->setName($name);
                $user->setPassword($this->getRandomPassword()); // Set a random password
                $user->setIsverified(true);
                $user->setSignupType(User::SIGNUP_TYPE_FACEBOOK);

                $redirectUrl = $userService->createUser($user, false);

                // Create a FB info
                $ufb = new UserFb();
                $ufb->setFbEmail($email);
                $ufb->setFbId($userId);
                $ufb->setUserInfo(json_encode($fbUser));
                $ufb->setAccessToken($fb->getAccessToken());
                $ufb->setUser($user);
                $em->persist($ufb);
                $em->flush();

                return $this->redirect($redirectUrl);
            }

        } catch(\FacebookApiException $e) {
            // TODO: Show error page
            $logger->info("FBAUTH: Api exception" . $e->getMessage());
            return null;
        }

    }

    private function createFacebookObj()
    {
        $config = array(
            'appId' => $this->container->getParameter('fb_app_id'),
            'secret' => $this->container->getParameter('fb_secret'),
            'allowSignedRequest' => false
        );

        $facebook = new \Facebook($config);

        return $facebook;
    }

    private function getRandomPassword()
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = substr( str_shuffle( $chars ), 0, 20 );

        return $str;
    }

}