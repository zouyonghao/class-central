<?php

namespace ClassCentral\SiteBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    private static $email;
    private static $password ='Test1234';
    private $loggedInClient = null;

    public static function setUpBeforeClass()
    {
        self::$email = sprintf("dhawal+%s@class-central.com",time());
    }

    public function testSignupForm()
    {
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

        // Add course to MOOC Tracker
        $this->addCourseToMOOCTracker($client);

        // Add search term to MOOC tracker
        $this->addSearchTermToMOOCTracker($client);

        // Logout
        $client->request('GET','/logout');
        // Assert following
        $crawler = $client->followRedirect();
        $this->isSignedOut($crawler);

    }


    public function testLoginForm()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Follow redirect due to https?
        $crawler = $client->followRedirect();       

        $form = $crawler->selectButton('Login')->form(array(
            '_username' => self::$email,
            '_password' => self::$password
        ));

        $client->submit($form);
        $crawler = $client->followRedirect();

        // Should be the homepage
        $this->assertTrue($crawler->filter('table[id=recentlist] tr')->count() > 0);

        // Check that the user is logged by going to the login page
        $client->request('GET', '/login');
        // Should redirect to the homepage
        $client->followRedirect();
        $this->assertTrue($crawler->filter('table[id=recentlist] tr')->count() > 0);

    }

    /**
     * User hits a page -> /signup/mooc/622
     * User Signsup
     * User is redirected to the MOOC tracker page
     * The course gets added to the mooc tracker page
     */
    /*
    public function testCourseReferralSignupFlow()
    {
         $client = self::createClient();
         $client->request('GET','/signup/mooc/622');
         $crawler = $client->followRedirect();
         $crawler = $client->followRedirect();

        // Fill the signup form
        $form = $crawler->selectButton('Sign up')->form(array(
            'classcentral_sitebundle_signuptype[email]' =>  sprintf("dhawal+%s@class-central.com",time()),
            'classcentral_sitebundle_signuptype[name]' => "Dhawal Shah",
            'classcentral_sitebundle_signuptype[password][password]' =>  self::$password,
            'classcentral_sitebundle_signuptype[password][confirm_password]' => self::$password
        ));

        $client->submit($form);

        $crawler = $client->followRedirect();
        $this->isSignedIn($crawler);
        // Check if course is added to mooc tracker
        $this->assertCount(1,
            $crawler->filter("div#mooc-tracker-course-box-content-title")
        );
    }
    */

    /**
     * User clicks on of the checkboxes and is redirect tp /signup/cc/courseId/listId
     * User signs up
     * User is redirected to the profile page
     * The course is added to profile page
     */
    public function testAddToLibraryFlowAction()
    {
        $client = self::createClient();
        $client->request('GET','/signup/cc/622/1');
        $crawler = $client->followRedirect();
        $crawler = $client->followRedirect();

        // Fill the signup form
        $form = $crawler->selectButton('Sign up')->form(array(
                'classcentral_sitebundle_signuptype[email]' =>  sprintf("dhawal+%s@class-central.com",time()),
                'classcentral_sitebundle_signuptype[name]' => "Dhawal Shah",
                'classcentral_sitebundle_signuptype[password][password]' =>  self::$password,
                'classcentral_sitebundle_signuptype[password][confirm_password]' => self::$password
            ));

        $client->submit($form);

        $crawler = $client->followRedirect();
        $this->isSignedIn($crawler);
        // Check if course is added to the library
        $this->assertCount(1,
            $crawler->filter("td[class=course-name-column]")
        );
    }

    /**
     * User hits a page -> /search?q=machine+learning
     * User Signsup
     * User is redirected to the MOOC tracker page
     * The course gets added to the mooc tracker page
     */
    public function testSearchTermReferralSignupFlow()
    {
        $client = self::createClient();
        $client->request('GET','/signup/q/machine%20learning');
        $crawler = $client->followRedirect();
        $crawler = $client->followRedirect();

        // Fill the signup form
        $form = $crawler->selectButton('Sign up')->form(array(
            'classcentral_sitebundle_signuptype[email]' =>  sprintf("dhawal+%s@class-central.com",time()),
            'classcentral_sitebundle_signuptype[name]' => "Dhawal Shah",
            'classcentral_sitebundle_signuptype[password][password]' =>  self::$password,
            'classcentral_sitebundle_signuptype[password][confirm_password]' => self::$password
        ));

        $client->submit($form);

        $crawler = $client->followRedirect();
        $this->isSignedIn($crawler);
        // Check if course is added to mooc tracker
        $this->assertGreaterThan(0,
            $crawler->filter("a:contains('machine learning')")->count()
        );
    }

    public function testMOOCTrackerRedirectToLoginPageForLoggedOutUser()
    {
        $client = self::createClient();
        $client->request('GET','/mooc-tracker');
        $crawler = $client->followRedirect();

        $this->isLoginPage($crawler);
    }

    private function login()
    {
        if($this->loggedInClient)
        {
            return $this->loggedInClient;
        }

        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Login')->form(array(
            '_username' => self::$email,
            '_password' => self::$password
        ));

        $client->submit($form);
        $client->followRedirect();
        $this->loggedInClient = $client;

        return $client;
    }

    private function  addCourseToMOOCTracker($client)
    {
        // Machine Learning course
        $crawler = $client->request('GET', '/mooc/835/coursera-machine-learning');
        // Add to MOOC tracker
        $client->click( $crawler->selectLink('track this course')->link() );
        $crawler = $client->followRedirect();
        $this->assertGreaterThan(0, $crawler->filter(':contains("added to MOOC tracker")')->count());
    }

    private function addSearchTermToMOOCTracker($client)
    {
        $crawler = $client->request('GET', '/search?q=machine+learning');
        // Add to MOOC tracker
        $client->click($crawler->selectLink('add "machine learning" to MOOC tracker')->link());
        $crawler = $client->followRedirect();
        $this->assertGreaterThan(0, $crawler->filter('a:contains("added "machine learning" to MOOC tracker")')->count());
    }


    public function isSignedOut($crawler)
    {
        $this->assertGreaterThan(0, $crawler->filter("a:contains('Signup for MOOC Tracker')")->count());
    }

    public function isSignedIn($crawler)
    {
        $this->assertGreaterThan(0, $crawler->filter("a:contains('MOOC Tracker')")->count());
    }

    private function isLoginPage($crawler)
    {
        $this->assertGreaterThan(0, $crawler->filter("html:contains('MOOC Tracker login')")->count());
    }
}
