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
use ClassCentral\SiteBundle\Services\Kuber;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookSession;
use Facebook\GraphObject;
use Facebook\GraphUser;
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
                'redirectUrl' => $this->getLastAccessedPage($session),
            )
        );
    }

    private function getLastAccessedPage($session)
    {
        $last_route = $session->get('this_route');
        $redirectUrl = null;
        if(! empty($last_route))
        {
            $redirectUrl = $this->generateUrl($last_route['name'], $last_route['params']);
        }

        return $redirectUrl;
    }
    /**
     * Redirects the user to fb auth url
     * @param Request $request
     */
    public function redirectToAuthorizationAction(Request $request)
    {
        $helper = $this->getFBLoginHelper();

        return $this->redirect( $helper->getLoginUrl(array(
            'public_profile',
            'email',
        )) );
    }

    private function getFBLoginHelper()
    {
        FacebookSession::setDefaultApplication(
            $this->container->getParameter('fb_app_id'),
            $this->container->getParameter('fb_secret')
        );

        $redirectUrl = $this->generateUrl(
            'fb_authorize_redirect',
            array(),
            true
        );

        return new FacebookRedirectLoginHelper( $redirectUrl);
    }

    public function fbReceiveAuthorizationCodeAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $userService = $this->get('user_service');
        $userSession = $this->get('user_session');
        $logger = $this->get('logger');

        $logger->info("FBAUTH: FB auth redirect");

        $helper = $this->getFBLoginHelper();

        try
        {
            $session = $helper->getSessionFromRedirect();
            if( !$session )
            {
                 // Redirect to the signup page
                $logger->info("FBAUTH: FB auth denied by the user");
                return $this->redirect($this->generateUrl('signup'));
            }



            $fbRequest = new FacebookRequest($session,'GET','/me');
            $fbUser = $fbRequest->execute()->getGraphObject(GraphUser::className());

            $email = $fbUser->getEmail();
            if(!$email)
            {
                // TODO : Render error page
                $logger->error("FBAUTH: Email missing");
                echo "Email is required. Please revoke Class Central App from your <a href='https://www.facebook.com/settings?tab=applications'>Facebook settings page</a> and then signup again.";
                exit();
            }
            $name = $fbUser->getName();
            $fbId = $fbUser->getId();

            // Check if the fb users has logged in before using the FB Id
            $usersFB = $em->getRepository('ClassCentralSiteBundle:UserFb')->findOneBy(array(
                'fbId' => $fbId
            ));

            if($usersFB)
            {
                $user = $usersFB->getUser();
            }
            else
            {
                // Check if an account with this email address exist. If it does then merge
                // these accounts
                $user = $em->getRepository('ClassCentralSiteBundle:User')->findOneBy(array(
                    'email' => $email
                ));
            }

            if($user)
            {
                $userService->login($user);
                $userSession->setPasswordLessLogin(true);
                // Check whether the user has fb details
                $ufb = $user->getFb();
                if($ufb)
                {
                    $logger->info("FBAUTH: FB user exists");
                }
                else
                {
                    $logger->info("FBAUTH: Email exists but UserFb table is empty");
                    // Create a FB info
                    $ufb = new UserFb();
                    $ufb->setFbEmail($email);
                    $ufb->setFbId($fbId);
                    $ufb->setUser($user);
                }

                $em->persist($ufb);
                $em->flush();

                $userSession->login($user);

                $redirectUrl =
                    ($this->getLastAccessedPage($request->getSession())) ?
                        $this->getLastAccessedPage($request->getSession()):
                        $this->generateUrl('user_library');

                $logger->info(' LOGIN REDIRECT URL ' . $redirectUrl);

                return $this->redirect( $redirectUrl );
            }
            else
            {
                $logger->info("FBAUTH: New user");
                $newsletterService = $this->get('newsletter');
                $newsletter = $em->getRepository('ClassCentralSiteBundle:Newsletter')->findOneByCode('mooc-report');


                // Create a new account
                $user = new User();
                $user->setEmail($email);
                $user->setName($name);
                $user->setPassword($this->getRandomPassword()); // Set a random password
                $user->setIsverified(true);
                $user->setSignupType(User::SIGNUP_TYPE_FACEBOOK);

                $redirectUrl = $userService->createUser($user, false, 'facebook');
                $userSession->setPasswordLessLogin(true); // Set the variable to show that the user didn't use a password to login

                // Create a FB info
                $ufb = new UserFb();
                $ufb->setFbEmail($email);
                $ufb->setFbId($fbId);
                $ufb->setUser($user);
                $em->persist($ufb);
                $em->flush();

                $this->uploadFacebookProfilePic( $user, $fbId);

                // Subscribe to newsletter
                $subscribed = $newsletterService->subscribeUser($newsletter, $user);
                $logger->info("preferences subscribed : email newsletter subscription", array(
                    'email' =>$user->getId(),
                    'newsletter' => $newsletter->getCode(),
                    'subscribed' => $subscribed
                ));

                return $this->redirect($redirectUrl);
            }

        }
        catch (FacebookRequestException $e)
        {
            $logger->info("FBAUTH: FB Auth error - " . $e->getMessage());
            return null;
        }
        catch (\Exception $e)
        {
            $logger->info("FBAUTH: Api exception" . $e->getMessage());
            return null;
        }

    }


    /**
     * Uploads the facebook profile picture as a users profile picture
     * @param User $user
     * @param $username
     */
    private function uploadFacebookProfilePic( User $user, $fbId)
    {
        try{
            $kuber = $this->get('kuber');

            $url = sprintf("https://graph.facebook.com/v2.3/%s/picture?type=large",$fbId);

            //Get the extension
            $size = getimagesize($url);
            $extension = image_type_to_extension($size[2]);
            $imgFile = '/tmp/'. $fbId;
            file_put_contents( $imgFile, file_get_contents($url)); // Gets a silhouette if image does not exist

            // Upload the file to S3 using Kuber
            $kuber->upload( $imgFile, Kuber::KUBER_ENTITY_USER, Kuber::KUBER_TYPE_USER_PROFILE_PIC, $user->getId(), ltrim($extension,'.'));
            // Clear the cache for profile pic
            $this->get('cache')->deleteCache('user_profile_pic_' . $user->getId());
            // Delete the temporary file
            unlink( $imgFile );
        } catch ( \Exception $e ) {
            $this->get('logger')->error(
                "Failed uploading Facebook Profile Picture for user id " . $user->getId() .
                ' with error: ' . $e->getMessage()
            );
        }

    }


    private function getRandomPassword()
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = substr( str_shuffle( $chars ), 0, 20 );

        return $str;
    }

}