<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 2/12/18
 * Time: 11:29 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Utility\Breadcrumb;
use ClassCentral\SiteBundle\Utility\UniversalHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use ClassCentral\SiteBundle\Entity\HelpGuideArticle;

class HelpGuidesAdminController extends Controller
{
    /**
     * the admin homepage
     * @param Request $request
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:HelpGuideArticle')->findAll();

        $adminBreadCrumbs = [];
        $adminBreadCrumbs[] = Breadcrumb::helpGuidesTopLevelBreadCrumb($this);
        return $this->render('ClassCentralSiteBundle:HelpGuides:Admin\helpguides_admin_index.html.twig', array(
            'entities' => $entities,
            'adminBreadCrumbs' => $adminBreadCrumbs,
            'articleStatuses' => HelpGuideArticle::$statuses
        ));
    }

    public function sectionsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('ClassCentralSiteBundle:HelpGuideSection')->findAll();

        $adminBreadCrumbs = [];
        $adminBreadCrumbs[] = Breadcrumb::helpGuidesTopLevelBreadCrumb($this);
        return $this->render('ClassCentralSiteBundle:HelpGuides:Admin\helpguides_admin_sections.html.twig', array(
            'entities' => $entities,
            'adminBreadCrumbs' => $adminBreadCrumbs
        ));
    }

    /**
     * Returns a url which when posted to uploads an image to S3
     * @param Request $request
     */
    public function getImageUploadUrlAction(Request $request)
    {

        $contentType = $request->query->get('content-type');
        $ext = $request->query->get('ext');
        if( empty($contentType) || empty($ext) )
        {
            return UniversalHelper::getAjaxResponse(false,"content-type and ext are required query params");
        }

        $kuber = $this->get('kuber');
        $s3params = $kuber->getImageUploadUrlForHelpGuides( $contentType, $ext );

        return UniversalHelper::getAjaxResponse(true, [
          'imageUrl' => $s3params['imageUrl'],
          'signedUrl' => $s3params['signedUrl'],
        ]);
    }
}
