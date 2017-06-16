<?php

namespace ClassCentral\SiteBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Tracking {
    private $container;

    const PAGEVIEW = 'pageview';
    const AD_CLICK = 'ad_click';
    const PAGEVIEW_INSTITUTION = 'pageview_institutition';
    const PAGEVIEW_SUBJECT = 'pageview_subject';


    public function __construct(ContainerInterface $container) {
      $this->container = $container;
    }
    public function event($key) {
      return constant('self::' . $key);
    }
}
