<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/21/14
 * Time: 8:45 PM
 */

namespace ClassCentral\SiteBundle\Services;
use ClassCentral\SiteBundle\Entity\Initiative;
use ClassCentral\SiteBundle\Entity\User;
use ClassCentral\SiteBundle\Entity\UserCourse;
use ClassCentral\SiteBundle\Utility\Breadcrumb;
use ClassCentral\SiteBundle\Utility\PageHeader\PageHeaderFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A service that builds data for course listing pages
 * like provider pages etc
 * Class CourseListing
 * @package ClassCentral\SiteBundle\Services
 */
class CourseListing {

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Retrieves all the data required for a particular provider
     * @param $slug
     * @param Request $request
     */
    public function byProvider($slug, Request $request)
    {
        $cache = $this->container->get('cache');
        $data = $cache->get(
            'provider_' . $slug . $request->server->get('QUERY_STRING'), function ($slug, $request) {

            $finder = $this->container->get('course_finder');

            $em = $this->container->get('doctrine')->getManager();

            if ($slug == 'others') {
                $provider = new Initiative();
                $provider->setName('Others');
                $provider->setCode('others');
            } elseif ($slug == 'independent') {
                $provider = new Initiative();
                $provider->setName('Independent');
                $provider->setCode('independent');
            } else {
                $provider = $em->getRepository('ClassCentralSiteBundle:Initiative')->findOneBy(array('code' => $slug));
                if (!$provider) {
                    throw new \Exception("Provider $slug not found");
                }
            }

            extract($this->getInfoFromParams($request->query->all()));
            $courses = $finder->byProvider($slug, $filters, $sort, $pageNo);
            extract($this->getFacets($courses));

            $pageInfo = PageHeaderFactory::get($provider);

            return compact(
                'provider', 'allSubjects', 'allLanguages', 'allSessions', 'courses',
                'sortField', 'sortClass', 'pageNo', 'pageInfo'
            );
        }, array($slug, $request));

        return $data;
    }

    /**
     * Retrieves all the data required for a particular provider
     * @param $slug
     * @param Request $request
     */
    public function bySubject($slug, Request $request)
    {
        $cache = $this->container->get('cache');
        $data = $cache->get(
            'subject_' . $slug . $request->server->get('QUERY_STRING'), function ($slug, $request) {

            $finder = $this->container->get('course_finder');

            $em = $this->container->get('doctrine')->getManager();

            $subject = $em->getRepository('ClassCentralSiteBundle:Stream')->findOneBySlug($slug);

            if(!$subject)
            {
                throw new \Exception("Subject $slug not found");
                return;
            }

            extract($this->getInfoFromParams($request->query->all()));
            $courses = $finder->bySubject($slug, $filters, $sort, $pageNo);
            extract($this->getFacets($courses));

            $pageInfo = PageHeaderFactory::get($subject);
            $pageInfo->setPageUrl(
               $this->container->getParameter('baseurl'). $this->container->get('router')->generate('ClassCentralSiteBundle_stream', array('slug' => $slug))
            );

            $breadcrumbs = array(
                Breadcrumb::getBreadCrumb('Subjects', $this->container->get('router')->generate('subjects')),
            );

            // Add parent stream to the breadcrumb if it exists
            if($subject->getParentStream())
            {
                $breadcrumbs[] = Breadcrumb::getBreadCrumb(
                    $subject->getParentStream()->getName(),
                    $this->container->get('router')->generate('ClassCentralSiteBundle_stream', array( 'slug' => $subject->getParentStream()->getSlug()))
                );
            }

            $breadcrumbs[] = Breadcrumb::getBreadCrumb($subject->getName());
            $subject->setParentStream( null ); // To avoid cache errors

            return compact(
                'subject', 'allSubjects', 'allLanguages', 'allSessions', 'courses',
                'sortField', 'sortClass', 'pageNo', 'pageInfo','breadcrumbs'
            );
        }, array($slug, $request));

        return $data;
    }

    public function byTime($status, Request $request)
    {
        $cache = $this->container->get('cache');
        $data = $cache->get(
            'course_status_' . $status . $request->server->get('QUERY_STRING'), function ($status, $request) {

            $finder = $this->container->get('course_finder');

            extract($this->getInfoFromParams($request->query->all()));
            $courses = $finder->byTime($status, $filters, $sort, $pageNo);
            extract($this->getFacets($courses));

            return compact(
               'allSubjects', 'allLanguages', 'courses',
                'sortField', 'sortClass', 'pageNo'
            );
        }, array($status, $request));

        return $data;
    }

    public function byInstitution($slug, Request $request)
    {
        $cache = $this->container->get('cache');
        $data = $cache->get(
            'institution_' . $slug . $request->server->get('QUERY_STRING'), function ($slug, $request) {

            $finder = $this->container->get('course_finder');
            $em = $this->container->get('doctrine')->getManager();

            $institution = $em->getRepository('ClassCentralSiteBundle:Institution')->findOneBySlug($slug);
            if(!$institution) {
                throw new \Exception("Institution/University $slug not found");
            }

            $pageInfo =  PageHeaderFactory::get($institution);
            $pageInfo->setPageUrl(
                $this->container->getParameter('baseurl'). $this->container->get('router')->generate('ClassCentralSiteBundle_institution', array('slug' => $slug))
            );

            extract($this->getInfoFromParams($request->query->all()));
            $courses = $finder->byInstitution($slug, $filters, $sort, $pageNo);
            extract($this->getFacets($courses));

            return compact(
                'allSubjects', 'allLanguages', 'allSessions', 'courses',
                'sortField', 'sortClass', 'pageNo', 'pageInfo', 'institution'
            );
        }, array($slug, $request));

        return $data;
    }

    public function byLanguage($slug, Request $request)
    {
        $cache = $this->container->get('cache');
        $data = $cache->get(
            'language_' . $slug . $request->server->get('QUERY_STRING'), function ($slug, $request) {

            $finder = $this->container->get('course_finder');
            $em = $this->container->get('doctrine')->getManager();

            $language = $em->getRepository('ClassCentralSiteBundle:Language')->findOneBySlug($slug);
            if(!$language) {
                throw new \Exception("Language $slug not found");
            }
            $pageInfo =  PageHeaderFactory::get($language);
            $pageInfo->setPageUrl(
                $this->container->getParameter('baseurl'). $this->container->get('router')->generate('lang', array('slug' => $slug))
            );

            $breadcrumbs = array(
                Breadcrumb::getBreadCrumb('Languages',$this->container->get('router')->generate('languages')),
                Breadcrumb::getBreadCrumb($language->getName(), $this->container->get('router')->generate('lang',array('slug' => $language->getSlug())))
            );

            extract($this->getInfoFromParams($request->query->all()));
            $courses = $finder->byLanguage($slug, $filters, $sort, $pageNo);
            extract($this->getFacets($courses));

            return compact(
                'allSubjects', 'allSessions', 'courses',
                'sortField', 'sortClass', 'pageNo', 'pageInfo', 'breadcrumbs','language'
            );
        }, array($slug, $request));

        return $data;
    }

    public function byTag($tag, Request $request)
    {
        $cache = $this->container->get('cache');
        $data = $cache->get(
            'tag_' . $tag . $request->server->get('QUERY_STRING'), function ($tag, $request) {

            $finder = $this->container->get('course_finder');

            extract($this->getInfoFromParams($request->query->all()));
            $courses = $finder->byTag($tag, $filters, $sort, $pageNo);
            extract($this->getFacets($courses));

            return compact(
                'allSubjects', 'allLanguages', 'allSessions', 'courses',
                'sortField', 'sortClass', 'pageNo'
            );
        }, array($tag, $request));

        return $data;
    }

    public function search($keyword, Request $request)
    {
        $finder = $this->container->get('course_finder');

        extract($this->getInfoFromParams($request->query->all()));
        $courses = $finder->search($keyword, $filters, $sort, $pageNo);
        extract($this->getFacets($courses));

        return compact(
            'allSubjects', 'allLanguages', 'allSessions', 'courses',
            'sortField', 'sortClass', 'pageNo'
        );
    }

    public function userLibrary(User $user, Request $request)
    {
        $finder = $this->container->get('course_finder');

        $userCourses = $user->getUserCourses();
        $courseIds = array();
        $listCounts = array();

        $lists = Filter::getUserList( $request->query->all() );
        foreach($lists as $list)
        {
            $listCounts[$list] = 0;
        }
        foreach($userCourses as $userCourse)
        {
            $list = $userCourse->getList();
            if( in_array( $list['slug'],$lists) )
            {
                $courseIds[] = $userCourse->getCourse()->getId();
                $listCounts[$list['slug']]++;
            }
        }

        extract($this->getInfoFromParams($request->query->all()));
        $courses = $finder->byCourseIds($courseIds, $filters, $sort, $pageNo);
        extract($this->getFacets($courses));

        return compact(
            'allSubjects', 'allLanguages', 'allSessions', 'courses',
            'sortField', 'sortClass', 'pageNo','lists', 'listCounts'
        );
    }

    public function getInfoFromParams($params = array())
    {
        $filters = Filter::getQueryFilters($params);
        $sort = Filter::getQuerySort($params);
        $pageNo = Filter::getPage($params);
        $sortField = '';
        $sortClass = '';
        if (isset($params['sort'])) {
            $sortDetails = Filter::getSortFieldAndDirection($params['sort']);
            $sortField = $sortDetails['field'];
            $sortClass = Filter::getSortClass($sortDetails['direction']);
        }

        return compact('filters', 'sort', 'pageNo', 'sortField', 'sortClass');
    }

    public function getFacets( $courses )
    {
        $finder =  $this->container->get('course_finder');
        $filter = $this->container->get('filter');
        $facets = $finder->getFacetCounts( $courses );
        $allSubjects = $filter->getCourseSubjects( $facets['subjectIds'] );
        $allLanguages = $filter->getCourseLanguages( $facets['languageIds'] );
        $allSessions  = $filter->getCourseSessions( $facets['sessions'] );

        return compact('allSubjects','allLanguages','allSessions');
    }
} 