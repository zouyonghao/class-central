<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 12/31/15
 * Time: 10:46 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Utility\UniversalHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class FollowController extends Controller
{

    public function followSubjectAction(Request $request, $subjectId)
    {
        $em = $this->getDoctrine()->getManager();
        $followService = $this->get('follow');
        $userSession = $this->get('user_session');

        $user = $this->get('security.context')->getToken()->getUser();
        if($user)
        {

            $subject = $em->getRepository('ClassCentralSiteBundle:Stream')->find($subjectId);
            if($subject)
            {
                $f = $followService->follow($user,$subject);
                if($f)
                {
                    // Update User Session
                    $userSession->saveFollowInformation();
                    return UniversalHelper::getAjaxResponse(true);
                }
                else
                {
                    return UniversalHelper::getAjaxResponse (false, "Follow Failed");
                }
            }
            else
            {
                return UniversalHelper::getAjaxResponse (false, "Subject not found");
            }
        }
        else
        {
            // No logged in user
            return UniversalHelper::getAjaxResponse (false, "User is not logged in");
        }
    }
}