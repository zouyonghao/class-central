<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 12/31/15
 * Time: 10:46 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Entity\Item;
use ClassCentral\SiteBundle\Entity\Offering;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Utility\UniversalHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class FollowController extends Controller
{

    public function followAction(Request $request, $item,$itemId)
    {
        $followService = $this->get('follow');
        $userSession = $this->get('user_session');

        $user = $this->get('security.context')->getToken()->getUser();
        if($user)
        {
            $f = $followService->followUsingItemInfo($user,$item,$itemId);
            if($f)
            {
                // Update User Session
                $userSession->saveFollowInformation($user);
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

    public function unfollowAction(Request $request, $item, $itemId)
    {
        $followService = $this->get('follow');
        $userSession = $this->get('user_session');

        $user = $this->get('security.context')->getToken()->getUser();
        if($user)
        {
            $f = $followService->unFollowUsingItemInfo($user,$item,$itemId);
            if($f)
            {
                // Update User Session
                $userSession->saveFollowInformation($user);
                return UniversalHelper::getAjaxResponse(true);
            }
            else
            {
                return UniversalHelper::getAjaxResponse (false, "Unfollowing Failed");
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

    public function personalizationAction(Request $request)
    {
        $cache = $this->get('cache');
        $userSession = $this->get('user_session');

        $providerController = new InitiativeController();
        $providersData = $providerController->getProvidersList($this->container);

        $insController = new InstitutionController();
        $insData = $insController->getInstitutions($this->container,true);

        $subjectsController = new StreamController();
        $subjects = $cache->get('stream_list_count', array($subjectsController, 'getSubjectsList'),array($this->container));

        $childSubjects = array();
        foreach($subjects['parent'] as $parent)
        {
            if( !empty($subjects['children'][$parent['id']]))
            {
                foreach($subjects['children'][$parent['id']] as $child)
                {
                    $childSubjects[] = $child;
                }
            }
        }

        // Count follows.
        $follows = $userSession->getFollows();
        $numSubjectsFollowed = count( $follows[Item::ITEM_TYPE_SUBJECT]);
        $numInstitutionsFollowed = count( $follows[Item::ITEM_TYPE_INSTITUTION] );
        $numProvidersFollowed = count( $follows[Item::ITEM_TYPE_PROVIDER]);

        return  $this->render('ClassCentralSiteBundle:Follow:personalization.html.twig',array(
            'providers' => $providersData['providers'],
            'followProviderItem' => Item::ITEM_TYPE_PROVIDER,
            'institutions' => $insData['institutions'],
            'followInstitutionItem' => Item::ITEM_TYPE_INSTITUTION,
            'page' => 'Personalization',
            'subjects' => $subjects,
            'childSubjects' => $childSubjects,
            'followSubjectItem' => Item::ITEM_TYPE_SUBJECT,
            'numSubjectsFollowed' => $numSubjectsFollowed,
            'numInstitutionFollowed' => $numInstitutionsFollowed,
            'numProvidersFollowed' => $numProvidersFollowed
        ));
    }

    /**
     * Show courses based on user recommendations
     */
    public function recommendationsAction(Request $request)
    {
        // Autologin if a token exists
        $this->get('user_service')->autoLogin($request);

        $user = $this->getUser();
        $suggestions = $this->get('suggestions');
         $data = $suggestions->getRecommendations($user,$request->query->all());
        //$data = $suggestions->byStartDate($user, '2016-02-01','2016-02-26');

        return $this->render('ClassCentralSiteBundle:Follow:courses.html.twig',
            array(
                'page'=>'user_course_recommendations',
                'results' => $data['courses'],
                'listTypes' => UserCourse::$lists,
                'allSubjects' => $data['allSubjects'],
                'allLanguages' => $data['allLanguages'],
                'offeringTypes' => Offering::$types,
                'sortField' => $data['sortField'],
                'sortClass' => $data['sortClass'],
                'pageNo' => $data['pageNo'],
                'showHeader' => true
            ));
    }

    /**
     * Generates a suggestions page by user id. Only open to admins
     * @param Request $request
     * @param $userId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function recommendationsByUserAction(Request $request, $userId)
    {
        $user = $this->getDoctrine()->getManager()->getRepository('ClassCentralSiteBundle:User')->find($userId);
        $suggestions = $this->get('suggestions');
        $data = $suggestions->getRecommendations($user,$request->query->all());
        // $data = $suggestions->newCoursesbyUser($user,30);
         //$data = $suggestions->byStartDate($user, '2016-02-01','2016-02-26');

        return $this->render('ClassCentralSiteBundle:Follow:courses.html.twig',
            array(
                'page'=>'user_course_recommendations',
                'results' => $data['courses'],
                'listTypes' => UserCourse::$lists,
                'allSubjects' => $data['allSubjects'],
                'allLanguages' => $data['allLanguages'],
                'offeringTypes' => Offering::$types,
                'sortField' => $data['sortField'],
                'sortClass' => $data['sortClass'],
                'pageNo' => $data['pageNo'],
                'showHeader' => true
            ));
    }

    /**
     *
     * @param Request $request
     * @param $userId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function personalizationByUserAction(Request $request, $userId)
    {
        $user = $this->getDoctrine()->getManager()->getRepository('ClassCentralSiteBundle:User')->find($userId);
        $userSession = $this->get('user_session');
        $userSession->saveFollowInformation($user);

        return $this->personalizationAction($request);

    }
}