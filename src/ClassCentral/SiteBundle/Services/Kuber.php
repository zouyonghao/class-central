<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 8/20/14
 * Time: 5:58 PM
 */

namespace ClassCentral\SiteBundle\Services;
use Aws\S3\S3Client;
use ClassCentral\SiteBundle\Entity\File;
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

    const KUBER_ENTITY_USER = 'User';

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
     * @param $file Path of the file
     * @param $entity
     * @param $type
     * @param $entity_id
     */
    public function upload( $file, $entity, $type, $entity_id)
    {
        $client = $this->getS3Client();
        $em = $this->container->get('doctrine')->getManager();
        $logger = $this->getLogger();

        $name = $this->generateFileName( $file );
        // Check if the file already exists
        $fileRecord = $em->getRepository('ClassCentralSiteBundle:File')->findOneBy(array(
            'entity' => $entity,
            'type'   => $type,
            'entityId' => $entity_id
        ));
        if( $fileRecord )
        {
            // Delete the original file
            try
            {
                $result = $client->deleteObject(array(
                    'Bucket' => $this->s3Bucket,
                    'Key'    => $fileRecord->getFileName()
                ));
            } catch(\Exception $e)
            {
                $logger->error( "Error trying to delete file during upload " . $e->getMessage(),array(
                    'Entity' => $entity,
                    'Entity_Id'=> $entity_id,
                    'Type' => $type
                ));
            }


            // Update the name
            $fileRecord->setFileName( $name );
            $fileRecord->setFileType( mime_content_type($file) );
        }
        else
        {
            $fileRecord =  new File();
            $fileRecord->setEntity( $entity );
            $fileRecord->setType( $type );
            $fileRecord->setEntityId( $entity_id );
            $fileRecord->setFileName( $name );
            $fileRecord->setFileType( mime_content_type($file) );
        }
        try
        {
            $result = $client->putObject(array(
                'Bucket' => $this->s3Bucket,
                'Key' => $name,
                'SourceFile' => $file
            ));
            $logger->info( "File uploaded for Entity $entity with type $type and Entity Id $entity_id",  (array)$result);

            $em->persist($fileRecord);
            $em->flush();

            return $fileRecord;
        } catch ( \Exception $e) {
            // Log the exception
            $logger->error( "Exception occurred while uploading file - " . $e->getMessage(),array(
                'Entity' => $entity,
                'Entity_Id'=> $entity_id,
                'Type' => $type
            ));
            return false;
        }

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

    private function  generateFileName( $filePath )
    {
        $fileParts = pathinfo($filePath);
        $time = microtime();

        return substr(md5( $this->generateRandomString() + $time ),0,12). '.' . $fileParts['extension'] ;
    }

    private function getLogger()
    {
        return $this->container->get('logger');
    }

    private function generateRandomString($length = 10) {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }
} 