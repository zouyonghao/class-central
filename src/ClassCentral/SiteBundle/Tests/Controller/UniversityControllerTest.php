<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/4/14
 * Time: 1:01 AM
 */

namespace ClassCentral\SiteBundle\Tests\Controller;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UniversityControllerTest extends WebTestCase
{
    public function testUniversitiesPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/universities');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /universities");
        $this->assertTrue($crawler->filter('table.list-of-universities tr')->count() > 0);
    }

    public function testUniversityDetailPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/university/stanford');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /university/stanford");
        $this->assertTrue($crawler->filter('h2.head-4.medium-up-head-3.block')->count() > 0);
    }
}
