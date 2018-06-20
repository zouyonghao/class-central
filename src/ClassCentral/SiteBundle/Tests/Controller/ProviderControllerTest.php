<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/4/14
 * Time: 1:01 AM
 */

namespace ClassCentral\SiteBundle\Tests\Controller;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProviderControllerTest extends WebTestCase
{
    public function testProvidersPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/providers');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /providers");
        $this->assertTrue($crawler->filter('table.list-of-universities tr')->count() > 0);
    }

    public function testProviderDetailPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/provider/coursera');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /provider/coursera");
        $this->assertTrue($crawler->filter('h1 strong.head-2.medium-up-head-1.text--bold')->count() > 0);
    }
}
