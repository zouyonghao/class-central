<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/4/14
 * Time: 1:01 AM
 */

namespace ClassCentral\SiteBundle\Tests\Controller;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SubjectControllerTest extends WebTestCase
{
    public function testSubjectsPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/subjects');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /subjects");
        $this->assertTrue($crawler->filter('div.single-category')->count() > 0);
    }

    public function testSubjectDetailPage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/subject/cs');
        $this->assertEquals(200, $client->getResponse()->getStatusCode(), "Unexpected HTTP status code for GET /subject/cs");
        $this->assertTrue($crawler->filter('table#subjectstablelist .table-body-subjectstable tr')->count() > 0);
    }
}
