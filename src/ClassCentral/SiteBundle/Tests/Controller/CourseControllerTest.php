<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/4/14
 * Time: 1:01 AM
 */

namespace ClassCentral\SiteBundle\Tests\Controller;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CourseControllerTest extends WebTestCase
{
    public function testTagPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/tag/writing');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /");
        $this->assertTrue($crawler->filter('table[id=tagtablelist] tr')->count() > 0);
    }

    public function testIndividualCoursePage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/course/coursera-machine-learning-835');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /course/coursera-machine-learning-835");
        $this->assertTrue($crawler->filter('#btnProviderCoursePage span')->count() > 0);
    }
}