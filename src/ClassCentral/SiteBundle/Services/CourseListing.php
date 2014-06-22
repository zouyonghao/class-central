<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/21/14
 * Time: 8:45 PM
 */

namespace ClassCentral\SiteBundle\Services;
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