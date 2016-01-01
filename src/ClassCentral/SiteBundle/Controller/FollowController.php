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

    public function followAction(Request $request, $item,$itemId)
    {
        $em = $this->getDoctrine()->getManager();
        $followService = $this->get('follow');
        $userSession = $this->get('user_session');

        $user = $this->get('security.context')->getToken()->getUser();
        if($user)
        {
            $f = $followService->followUsingItemInfo($user,$item,$itemId);
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
            // No logged in user
            return UniversalHelper::getAjaxResponse (false, "User is not logged in");
        }
    }

    public function preFollowAction(Request $request, $item, $itemId)
    {
        $userSession = $this->get('user_session');
        $userSession->saveAnonActivity('follow',"$item-$itemId");
        return UniversalHelper::getAjaxResponse(true);
    }
}