<?php

namespace ClassCentral\SiteBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    private static $email;
    private static $password ='Test1234';

    public static function setUpBeforeClass()
    {
        self::$email = sprintf("dhawal+%s@class-central.com",time());
    }

    public function testSignupForm()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/signup');
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

}
