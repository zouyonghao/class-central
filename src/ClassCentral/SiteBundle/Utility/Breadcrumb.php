<?php

namespace ClassCentral\SiteBundle\Utility;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Breadcrumb {

    /**
     * Returns an array representing an item in the breadcrumb.
     * The page being displayed should be entered last
     * @param $name display name for the page
     * @param string $url for the page
     * @return array
     */
    public static function  getBreadCrumb($name, $url = '')
    {
        return array(
            'name' => $name,
            'url' => $url
        );
    }

    public static function helpGuidesTopLevelBreadCrumb(Controller $controller)
    {
        return self::getBreadCrumb(
            "Help Guides",
            $controller->generateUrl('help_guides_admin_article_index')
        );
    }

}