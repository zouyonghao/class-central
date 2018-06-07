<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 11/11/17
 * Time: 9:41 PM
 */

namespace ClassCentral\SiteBundle\Controller;


use ClassCentral\SiteBundle\Entity\UserCourse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class OpenSchoolController extends Controller
{
    public function showAction(Request $request, $school = null)
    {
      $this->get('user_service')->autoLogin($request);
      $cache = $this->get('cache');

      $jsonFilePath = $this->get('kernel')->getRootDir() . '/../src/ClassCentral/SiteBundle/Resources/views/OpenSchool/data/' . $school . '.json';

      if (file_exists($jsonFilePath)) {
        $page = json_decode(file_get_contents($jsonFilePath), true);
      } else {
        throw $this->createNotFoundException("Tag not found");
      }

      $cl = $this->get('course_listing');

      foreach ($page["sections"] as $sectionKey => $section) {
        $page["sections"][$sectionKey]["tags"] = [];
        foreach ($section['tags'] as $tagKey => $tagObj) {
          array_push(
            $page["sections"][$sectionKey]["tags"],
            [
              'slug' => $tagObj['slug'],
              'key' => preg_replace('/\s/', '', $tagObj['slug']),
              'name' => $tagObj['name'],
              'data' => $cl->byTag($tagObj['slug'], $request)
            ]
          );
        }
      }

      $page["school"] = $school;
      $page["listTypes"] = UserCourse::$lists;

      return $this->render('ClassCentralSiteBundle:OpenSchool:show.html.twig', $page);
    }
}
