<?php

namespace ClassCentral\SiteBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class UserControllerTest extends WebTestCase
{
    private static $email;
    private static $password ='test1234';
    private $loggedInClient = null;

    public static function setUpBeforeClass()
    {
        self::$email = sprintf("test+%s@classcentral.test",mt_rand());
    }

    public function testSignupForm()
    {
        $client = static::createClient(array(),array('HTTPS' => true));

        $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /");

        $client->request(
            'POST',
            '/ajax/signup/navbar_create_free_account'
        );

        $content = json_decode($client->getResponse()->getContent(), true)['modal'];
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /");
        $this->assertNotEmpty($content, 'Unable to get signup form through AJAX call');

        $crawler = new Crawler(null, $client->getRequest()->getSchemeAndHttpHost() . $client->getRequest()->getRequestUri());
        $crawler->addContent($content);

        // Fill the signup form
        $form = $crawler->selectButton('Sign Up')->form(array(
            'classcentral_sitebundle_signuptype[email]' => self::$email,
            'classcentral_sitebundle_signuptype[name]' => "Test User",
            'classcentral_sitebundle_signuptype[password]' =>  self::$password,
        ));

        $client->submit($form);
        $this->assertEquals('{"success":true,"message":""}', $client->getResponse()->getContent(), "Unexpected AJAX response for sign in attempt");

        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /");

        $this->isSignedIn($crawler);

        // Logout
        $client->request('GET','/logout');
        // Assert following
        $crawler = $client->followRedirect();
        $this->isSignedOut($crawler);
    }


    public function testLoginForm()
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        // Follow redirect due to https?
        $crawler = $client->followRedirect();

        $form = $crawler->filterXPath('//*[@id="auth-login"]/form/div/fieldset/button')->form(array(
            '_username' => self::$email,
            '_password' => self::$password
        ));

        $client->submit($form);
        $crawler = $client->followRedirect();

        $this->isSignedIn($crawler);

        // Add course to users library
//        $this->addCourseToUsersLibrary($client);

        // Add search term to MOOC tracker
//        $this->addSearchTermToMOOCTracker($client);

        // Check the default preferences page are correctly populated
        $crawler = $client->request('GET','/user/preferences');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /user/preferences");

        // Mooc tracker preferences are ON by default
        $this->assertEquals(3, $crawler->filter("input[class=mooc-tracker-checkbox]:checked")->count(), "MOOC Tracker preferences are not correct");

        // Only one newsletter has been subscribed too
        $this->assertEquals(1, $crawler->filter("input[class=user-newsletter-checkbox]:checked")->count(), "Newsletter preferences are not correct");

        // Check that the user is logged by going to the login page
        $client->request('GET', '/login');

        // Should redirect to the homepage
        $client->followRedirect();
        $this->assertEquals('/', $client->getRequest()->getRequestUri(), "Should be redirected to homepage if logged in");

        // Logout
        $client->request('GET','/logout');

        // Assert following
        $crawler = $client->followRedirect();
        $this->isSignedOut($crawler);
    }

    public function testLoginRedirects()
    {
        $client = static::createClient();

        // Request a create review page and get redirected to the signup page
        $client->request('GET','/signup/review/622');
        $client->followRedirect();

        // Request the login page
        $crawler = $client->request('GET', '/login');

        // Login
        $form = $crawler->filterXPath('//*[@id="auth-login"]/form/div/fieldset/button')->form(array(
            '_username' => self::$email,
            '_password' => self::$password
        ));

        $client->submit($form);
        $client->followRedirect();

        // Get redirected to the create review page
        $this->assertEquals('/review/new/622', $client->getResponse()->headers->get('location'));
    }

//    /**
//     * User hits a page -> /signup/mooc/622
//     * User Signsup
//     * User is redirected to the MOOC tracker page
//     * The course gets added to the mooc tracker page
//     */
//    /*
//    public function testCourseReferralSignupFlow()
//    {
//         $client = self::createClient();
//         $client->request('GET','/signup/mooc/622');
//         $crawler = $client->followRedirect();
//         $crawler = $client->followRedirect();
//
//        // Fill the signup form
//        $form = $crawler->selectButton('Sign up')->form(array(
//            'classcentral_sitebundle_signuptype[email]' =>  sprintf("dhawal+%s@class-central.com",time()),
//            'classcentral_sitebundle_signuptype[name]' => "Dhawal Shah",
//            'classcentral_sitebundle_signuptype[password][password]' =>  self::$password,
//            'classcentral_sitebundle_signuptype[password][confirm_password]' => self::$password
//        ));
//
//        $client->submit($form);
//
//        $crawler = $client->followRedirect();
//        $this->isSignedIn($crawler);
//        // Check if course is added to mooc tracker
//        $this->assertCount(1,
//            $crawler->filter("div#mooc-tracker-course-box-content-title")
//        );
//    }
//    */
//
//    /**
//     * User clicks on of the checkboxes and is redirect tp /signup/cc/courseId/listId
//     * User signs up
//     * User is redirected to the profile page
//     * The course is added to profile page
//     */
//    public function testAddToLibraryFlowAction()
//    {
//        $client = self::createClient();
//        $client->request('GET','/signup/cc/622/1');
//        $crawler = $client->followRedirect();
//        $crawler = $client->followRedirect();
//
//        // Fill the signup form
//        $form = $crawler->selectButton('Sign Up')->form(array(
//                'classcentral_sitebundle_signuptype[email]' =>  sprintf("dhawal+%s@class-central.com",mt_rand()),
//                'classcentral_sitebundle_signuptype[name]' => "Dhawal Shah",
//                'classcentral_sitebundle_signuptype[password][password]' =>  self::$password,
//                'classcentral_sitebundle_signuptype[password][confirm_password]' => self::$password
//            ));
//
//        $client->submit($form);
//
//        $crawler = $client->followRedirect();
////        $this->isSignedIn($crawler);
//        // Check if course is added to the library
////        $this->assertCount(1,
////            $crawler->filter("td[class=course-name-column]")
////        );
//    }
//
//    /**
//     * User hits a page -> /search?q=machine+learning
//     * User Signsup
//     * User is redirected to the MOOC tracker page
//     * The course gets added to the mooc tracker page
//     */
//    public function testSearchTermReferralSignupFlow()
//    {
//        $client = self::createClient();
//        $client->request('GET','/signup/q/machine%20learning');
//        $crawler = $client->followRedirect();
//        $crawler = $client->followRedirect();
//
//        // Fill the signup form
//        $form = $crawler->selectButton('Sign Up')->form(array(
//            'classcentral_sitebundle_signuptype[email]' =>  sprintf("dhawal+%s@class-central.com",mt_rand()),
//            'classcentral_sitebundle_signuptype[name]' => "Dhawal Shah",
//            'classcentral_sitebundle_signuptype[password][password]' =>  self::$password,
//            'classcentral_sitebundle_signuptype[password][confirm_password]' => self::$password
//        ));
//
//        $client->submit($form);
//
//        $crawler = $client->followRedirect();
//        $this->isSignedIn($crawler);
//        // Check if course is added to users library
//        $this->assertGreaterThan(0,
//            $crawler->filter("a:contains('machine learning')")->count()
//        );
//    }
//
//    public function testMyCoursesPageRedirectToLoginPage()
//    {
//        $client = self::createClient();
//        $client->request('GET','/user/courses');
//        $crawler = $client->followRedirect();
//
//        $this->isLoginPage($crawler);
//    }
//    private function addCourseToUsersLibrary($client)
//    {
//        $crawler = $client->request('GET','/ajax/user/course/add?c_id=1261&l_id=1');
//        $response = json_decode($crawler->text(),true);
//        $this->assertTrue($response['success'],"Course was not added to user");
//
//        // Go to the library page and check of the course exists there
//        $crawler = $client->request('GET','/user/courses');
//        $this->assertCount(1,
//            $crawler->filter("td[class=course-name-column]")
//        );
//    }
//
//    private function  addCourseToMOOCTracker($client)
//    {
//        // Machine Learning course
//        $crawler = $client->request('GET', '/mooc/835/coursera-machine-learning');
//        // Add to MOOC tracker
//        $client->click( $crawler->selectLink('track this course')->link() );
//        $crawler = $client->followRedirect();
//        $this->assertGreaterThan(0, $crawler->filter(':contains("added to MOOC tracker")')->count());
//    }
//
//    private function addSearchTermToMOOCTracker($client)
//    {
//        $crawler = $client->request('GET', '/search?q=machine+learning');
//        // Add to MOOC tracker
//        $client->click($crawler->selectLink('create alerts for "machine learning"')->link());
//        $crawler = $client->followRedirect();
//        $this->assertGreaterThan(0, $crawler->filter('a:contains("added "machine learning" to MOOC tracker")')->count());
//    }

    public function isSignedOut($crawler)
    {
        $this->assertGreaterThan(0, $crawler->filter("a:contains('Sign in')")->count());
    }

    public function isSignedIn($crawler)
    {
       $this->assertGreaterThan(0, $crawler->filter("a:contains('My Courses')")->count());
    }

    private function isLoginPage($crawler)
    {
        $this->assertGreaterThan(0, $crawler->filter("html:contains('Login')")->count());
    }
}
