<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 12/24/13
 * Time: 4:54 PM
 */

namespace ClassCentral\SiteBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerAjaxTest extends WebTestCase{

    private static $email;
    private static $password ='Test1234';
    private $loggedInClient = null;

    public static function setUpBeforeClass()
    {
        self::$email = sprintf("dhawal+%s@class-central.com",time());
    }

    public function testInterestedCoursesAjaxCall()
    {
        // Signup a new user

        $client = static::createClient();
        $crawler = $client->request('GET', '/signup');
        $crawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /signup");

        // Fill the signup form
        $form = $crawler->selectButton('Sign up')->form(array(
                'classcentral_sitebundle_signuptype[email]' => self::$email,
                'classcentral_sitebundle_signuptype[name]' => "Dhawal Shah",
                'classcentral_sitebundle_signuptype[password][password]' =>  self::$password,
                'classcentral_sitebundle_signuptype[password][confirm_password]' => self::$password
            ));

        $client->submit($form);

        $crawler = $client->followRedirect();
        $this->isSignedIn($crawler);

        // Add a course for the signed in user
        $crawler = $client->request('GET','/ajax/user/course/add?c_id=1261&l_id=1');
        $response = json_decode($crawler->text(),true);
        $this->assertTrue($response['success'],"Course was not added to user");

        // Add the course again and it should fail
        $crawler = $client->request('GET','/ajax/user/course/add?c_id=1261&l_id=1');
        $response = json_decode($crawler->text(),true);
        $this->assertFalse($response['success'],"Course was added to user when it should not have been");

        // Remove the course now
        $crawler = $client->request('GET','/ajax/user/course/remove?c_id=1261&l_id=1');
        $response = json_decode($crawler->text(),true);
        $this->assertTrue($response['success'],"Course could not be removed");

        // Remove should fail
        $crawler = $client->request('GET','/ajax/user/course/remove?c_id=1261&l_id=1');
        $response = json_decode($crawler->text(),true);
        $this->assertFalse($response['success'],"Course does not exist. Remove should fail");

        // Add the course again and it should work
        $crawler = $client->request('GET','/ajax/user/course/add?c_id=1261&l_id=1');
        $response = json_decode($crawler->text(),true);
        $this->assertTrue($response['success'],"Course was not added to user");

    }

    public function isSignedIn($crawler)
    {
        $this->assertGreaterThan(0, $crawler->filter("a:contains('MOOC Tracker')")->count());
    }
} 