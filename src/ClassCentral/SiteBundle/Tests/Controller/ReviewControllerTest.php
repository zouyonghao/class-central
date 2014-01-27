<?php

namespace ClassCentral\SiteBundle\Tests\Controller;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReviewControllerTest extends WebTestCase {

    private static $email;
    private static $password ='Test1234';
    private $loggedInClient = null;

    public static function setUpBeforeClass()
    {
        self::$email = sprintf("dhawal+%s@class-central.com",time());
    }

    public  function testReviewFunctionality()
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

        $crawler = $client->submit($form);

        $crawler = $client->followRedirect();
        $this->isSignedIn($crawler);


        // Render the create review page
        $client->request('GET','/user/review/new/622');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET create review page");

        // Create review should fail --> rating is not valid
        $reviewData = $this->getReviewData();
        $reviewData['rating'] = 6;
        $crawler = $client->request('POST','/user/review/create/622',array(),array(),array(),json_encode($reviewData));
        $response = json_decode($crawler->text(),true);
        $this->assertFalse($response['success'],"Review was created when the rating was invalid");

        // Create review should fail --> review text less than 20 characters
        $reviewData = $this->getReviewData();
        $reviewData['reviewText'] = 'This is less than 20 words';
        $crawler = $client->request('POST','/user/review/create/622',array(),array(),array(),json_encode($reviewData));
        $response = json_decode($crawler->text(),true);
        $this->assertFalse($response['success'],"Review was created when the review lest was less than 20 words");

        // Create review should fail --> contains a review id which means its an edit
        $reviewData = $this->getReviewData();
        $reviewData['reviewId'] =5;
        $crawler = $client->request('POST','/user/review/create/622',array(),array(),array(),json_encode($reviewData));
        $response = json_decode($crawler->text(),true);
        $this->assertFalse($response['success'],"Review was created when the call was an actual edit review call");

        // Make an ajax call to create review route
        $crawler = $client->request('POST','/user/review/create/622',array(),array(),array(),json_encode($this->getReviewData()));
        $response = json_decode($crawler->text(),true);
        $this->assertTrue($response['success'],"Review was not created");
        $reviewId = $response['message'];

        // Request the edit review page
        $client->request('GET','/user/review/edit/'+$reviewId);
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET create review page");

        // Edit the review
        $reviewData = $this->getReviewData();
        $reviewData['reviewId'] = $reviewId;
        $crawler = $client->request('POST','/user/review/create/622',array(),array(),array(),json_encode($reviewData));
        $response = json_decode($crawler->text(),true);
        $this->assertTrue($response['success'],"Review was not edited");
    }

    private function getReviewData()
    {
        return  array(
            'rating' => 4,
            'effort' => 10,
            'progress' => 1,
            'reviewText' => ""
        );
    }

    /**
     * Tests a flow when someone who is not logged in clicks on the create review button
     */
    public function testCreateReviewSignupFlow()
    {
        $client = static::createClient();

        $client->request('GET','/signup/review/622');
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

        // Confirm if its the write a new review form
        $this->assertGreaterThan(0, $crawler->filter("span:contains('Progress')")->count());
    }


    public function isSignedIn($crawler)
    {
        $this->assertGreaterThan(0, $crawler->filter("a:contains('My Courses')")->count());
    }

} 