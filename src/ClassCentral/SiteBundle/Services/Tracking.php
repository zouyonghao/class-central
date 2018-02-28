<?php

namespace ClassCentral\SiteBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Tracking {
    private $container;

    const PAGEVIEW = 'pageview';
    const PAGEVIEW_INSTITUTION = 'pageview_institutition';
    const PAGEVIEW_SUBJECT = 'pageview_subject';
    const AD_CLICK = 'ad_click';
    const AD_IMPRESSION = 'ad_impression';
    const TIP_CLICK = 'tip_click';
    const GO_TO_CLASS_CLICK = 'go_to_class_click';
    const HOMEPAGE_CLICK = 'homepage_click';
    const INTERLUDE_CLICK = 'interlude_click';
    const LISTING_CLICK = 'listing_click';
    const FOOTER_CLICK = 'footer_click';
    const AUTH_CLICK = 'auth_click';
    const REVIEW_CLICK = 'review_click';
    const SUBJECT_CLICK = 'subject_click';
    const HTTP_ERROR = 'http_error';
    const LANDING_PAGE_CLICK = 'landing_page_click';
    const FILTER_CLICK = 'filter_click';
    const COURSE_CLICK = 'course_click';
    const COURSERA_DEGREE_CLICK = 'coursera_degree_click';


    public function __construct(ContainerInterface $container) {
      $this->container = $container;
    }
    public function event($key) {
      return constant('self::' . $key);
    }
    public function device()
    {
      $detect = new \Mobile_Detect();
      return ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'desktop');
    }
}
