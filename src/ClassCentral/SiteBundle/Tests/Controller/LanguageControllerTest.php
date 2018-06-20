<?php

namespace ClassCentral\SiteBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LanguageControllerTest extends WebTestCase {

    public function testLanguagePage()
    {
//        $this->markTestSkipped('must be revisited.');

        $client = static::createClient();

        $crawler = $client->request('GET','/language/arabic');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /");
        $this->assertTrue($crawler->filter('h3.head-3.margin-bottom-small')->count() > 0);
    }

    public function testLanguagesPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET','/languages');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /");
        // Simple check to see if there are languages being displayed
        $this->assertTrue($crawler->filter('a.show-all-subjects')->count() > 0);
    }
}