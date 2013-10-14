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
            'classcentral_sitebundle_signuptype[password][password]' =>  self::$password,
            'classcentral_sitebundle_signuptype[password][confirm_password]' => self::$password
        ));

        $client->submit($form);

        $crawler = $client->followRedirect();

        // Should be in homepage.
        $this->assertTrue($crawler->filter('table[id=recentlist] tr')->count() > 0);


        // Check that the user is logged by going to the login page
        $client->request('GET', '/login');
        // Should redirect to the homepage
        $client->followRedirect();
        $this->assertTrue($crawler->filter('table[id=recentlist] tr')->count() > 0);


        // Add course to MOOC Tracker
        $this->addCourseToMOOCTracker($client);

        // Add search term to MOOC tracker
        $this->addSearchTermToMOOCTracker($client);


        // Logout
        $client->request('GET','/logout');
        // Assert following
        $client->followRedirect();
        $this->assertTrue($crawler->filter('table[id=recentlist] tr')->count() > 0);

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

    /*
    public function testAddCourseToMOOCTracker()
    {
        $this->addCourseToMOOCTracker($this->login());

    }

    public function testAddSearchTermToMOOCTracker()
    {
        $this->addSearchTermToMOOCTracker($this->login());
    }
    */



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
        $client->click( $crawler->selectLink('Add to MOOC tracker')->link() );
        $crawler = $client->followRedirect();
        $this->assertGreaterThan(0, $crawler->filter('a:contains("Added to MOOC tracker")')->count());
    }

    private function addSearchTermToMOOCTracker($client)
    {
        $crawler = $client->request('GET', '/search?q=machine+learning');
        // Add to MOOC tracker
        $client->click($crawler->selectLink('Add search term to MOOC tracker')->link());
        $crawler = $client->followRedirect();
        $this->assertGreaterThan(0, $crawler->filter('a:contains("Added search term to MOOC tracker")')->count());
    }

}
