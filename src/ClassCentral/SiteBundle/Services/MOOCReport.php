<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/29/16
 * Time: 1:00 PM
 */

namespace ClassCentral\SiteBundle\Services;

use Guzzle\Http\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pull in posts from MOOC Report
 * Class MOOCReport
 * @package ClassCentral\SiteBundle\Services
 */
class MOOCReport
{
    private $container;
    public static $baseUrl = 'https://www.class-central.com/report';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getPosts()
    {
        $client = new Client();
        $request = $client->createRequest('GET', self::$baseUrl . '/wp-json/wp/v2/posts');
        $response = $request->send();

        if($response->getStatusCode() !== 200)
        {
            throw new  \Exception('Error pulling down posts');
        }

        return json_decode($response->getBody(true),true);
    }

    public function getAuthor($authorId)
    {
        $client = new Client();
        $request = $client->createRequest('GET', self::$baseUrl . '/wp-json/wp/v2/users/'. $authorId);
        $response = $request->send();

        if($response->getStatusCode() !== 200)
        {
            throw new  \Exception('Error pulling down authors');
        }

        return json_decode($response->getBody(true),true);
    }

}