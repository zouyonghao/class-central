<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 2/15/16
 * Time: 3:02 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Entity\Item;
use ClassCentral\SiteBundle\Entity\Profile;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class OnboardingController extends Controller
{

    public function stepProfileAction(Request $request)
    {
        $user = $this->getUser();
        $profile = ($user->getProfile()) ? $user->getProfile() : new Profile();

        $html = $this->render('ClassCentralSiteBundle:Onboarding:profile.html.twig',
            array(
                'user' => $user,
                'profile'=> $profile,
                'degrees' => Profile::$degrees,
            ))
            ->getContent();

        $response = array(
            'modal' => $html
        );

        return new Response( json_encode($response) );
    }

    public function stepFollowSubjectsAction(Request $request)
    {
        $user = $this->getUser();
        $userSession = $this->get('user_session');
        $cache = $this->get('cache');

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
        $follows = $userSession->getFollows();

        $html = $this->render('ClassCentralSiteBundle:Onboarding:followsubjects.html.twig',
            array(
                'user' => $user,
                'subjects' => $subjects,
                'childSubjects' => $childSubjects,
                'followSubjectItem' => Item::ITEM_TYPE_SUBJECT
            ))
            ->getContent();

        $response = array(
            'modal' => $html,
        );

        return new Response( json_encode($response) );
    }

    public function stepFollowInstitutionsAction(Request $request)
    {
        $user = $this->getUser();
        $userSession = $this->get('user_session');
        $cache = $this->get('cache');

        $insController = new InstitutionController();
        $insData = $insController->getInstitutions($this->container,true);

        $providerController = new InitiativeController();
        $providersData = $providerController->getProvidersList($this->container);

        $html = $this->render('ClassCentralSiteBundle:Onboarding:followInstitutions.html.twig',
            array(
                'user' => $user,
                'followInstitutionItem' => Item::ITEM_TYPE_INSTITUTION,
                'followProviderItem' => Item::ITEM_TYPE_PROVIDER,
                'institutions' => $insData['institutions'],
                'providers' => $providersData['providers'],
            ))
            ->getContent();

        $response = array(
            'modal' => $html,
        );

        return new Response( json_encode($response) );
    }
}