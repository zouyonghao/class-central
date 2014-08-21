<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 8/20/14
 * Time: 5:58 PM
 */

namespace ClassCentral\SiteBundle\Services;
use Aws\S3\S3Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This is the file management API
 * Class Kuber
 * @package ClassCentral\SiteBundle\Services
 */
class Kuber {

    private $container;

    private  $s3Client;
    private  $awsAccessKey;
    private  $awsAccessSecret;
    private  $s3Bucket;

    private function getS3Client()
    {
        if(!$this->s3Client)
        {
            $this->s3Client = S3Client::factory(array(
                'key' => $this->awsAccessKey,
                'secret' => $this->awsAccessSecret,
            ));
        }

        return $this->s3Client;
    }

    public function __construct(ContainerInterface $container, $aws_access_key, $aws_access_secret, $s3_bucket)
    {
        $this->container = $container;
        $this->awsAccessKey = $aws_access_key;
        $this->awsAccessSecret = $aws_access_secret;
        $this->s3Bucket = $s3_bucket;
    }

    /**
     * Uploads the file
     * @param $file
     * @param $entity
     * @param $type
     * @param $entity_id
     */
    public function upload( $file, $entity, $type, $entity_id)
    {
        $client = $this->getS3Client();
        return $client->putObject(array(
            'Bucket' => $this->s3Bucket,
            'Key' => 'random_key',
            'Body' => 'hello world!'
        ));
    }

    /**
     * Returns a url for that particular file
     * @param $entity
     * @param $type
     * @param $entity_id
     */
    public function getUrl($entity,$type,$entity_id)
    {

    }
} 