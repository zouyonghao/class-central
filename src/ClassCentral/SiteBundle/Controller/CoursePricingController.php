<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 11/11/17
 * Time: 9:41 PM
 */

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CoursePricingController extends Controller
{
    public function showAction(Request $request, $table)
    {
        $jsonFilePath = $this->get('kernel')->getRootDir() . '/../src/ClassCentral/SiteBundle/Resources/views/Pricing/Data/' . $table . '.json';

        if (file_exists($jsonFilePath)) {
          $json = json_decode(file_get_contents($jsonFilePath), true);
        } else {
          throw $this->createNotFoundException("Pricing table does not exist");
        }

        return $this->render('ClassCentralSiteBundle:Pricing:table.html.twig', $json);
    }
}
