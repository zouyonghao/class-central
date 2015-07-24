<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/4/15
 * Time: 6:56 PM
 */

namespace ClassCentral\SiteBundle\Services;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Image Manipulation library
 * @package ClassCentral\SiteBundle\Services
 */
class Image {

    private  $apiKey;
    private  $embedlyDisplayBaseUrl = 'https://i.embed.ly/1/display';
    private $container;
    private $kuber;

    public function __construct( ContainerInterface $container, $apiKey )
    {
        $this->container = $container;
        $this->apiKey = $apiKey;
        $this->kuber = $container->get('kuber');
    }

    /**
     * Crops the image to a particular size
     * @param $imageUrl
     * @param $height
     * @param $width
     * @return string
     */
    public function cropImage($imageUrl, $height, $width)
    {
        return $this->embedlyDisplayBaseUrl.
        sprintf('/crop?url=%s&key=%s&height=%d&width=%d&grow=true',urlencode($imageUrl),$this->apiKey,$height,$width);
    }

    /**
     * @param $imageUrl src image url
     * @param $height height of the cropped image
     * @param $width  width of the cropped image
     * @param $kuberEntity Course, Credential, User etc.
     * @param $kuberImageType type of image
     * @param $kuberEntityId  course_id, user_id
     * @param null $extension  File extension for the image
     * @return mixed
     */
    public function cropAndSaveImage($imageUrl, $height, $width, $kuberEntity, $kuberImageType, $kuberEntityId, $extension = null)
    {
        $uniqueKey = $kuberImageType . "_{$height}x{$width}_" . basename($imageUrl);
        if( $this->kuber->hasFileChanged( $kuberEntity, $kuberImageType, $kuberEntityId ,$uniqueKey ) )
        {
            // Upload the hew file
            $croppedImageUrl = $this->cropImage( $imageUrl, $height, $width );

            // Upload the file
            $filePath = '/tmp/modified_'.$uniqueKey;
            file_put_contents($filePath,file_get_contents($croppedImageUrl));

            $file = $this->kuber->upload(
                $filePath,
                $kuberEntity,
                $kuberImageType,
                $kuberEntityId,
                $extension,
                $uniqueKey
            );

            return $this->kuber->getUrlFromFile( $file );
        }

        // File exists
        return $this->kuber->getUrl(
            $kuberEntity,
            $kuberImageType,
            $kuberEntityId
        );
    }

    // Given an image its returns the image in spotlight sized
    public function getSpotlightImage($imageURl, $spotlightId)
    {
        $uniqueKey = 'spl1'. basename($imageURl );

        // Check if the file exists or has changed.
        if( $this->kuber->hasFileChanged( Kuber::KUBER_ENTITY_SPOTLIGHT,Kuber::KUBER_TYPE_SPOTLIGHT_IMAGE, $spotlightId ,$uniqueKey ) )
        {
            // Upload the hew file
            $croppedImageUrl = $this->cropImage( $imageURl, 160, 198 );

            // Upload the file
            $filePath = '/tmp/modified_'.$uniqueKey;
            file_put_contents($filePath,file_get_contents($croppedImageUrl));

            $file = $this->kuber->upload(
                $filePath,
                Kuber::KUBER_ENTITY_SPOTLIGHT,
                Kuber::KUBER_TYPE_SPOTLIGHT_IMAGE,
                $spotlightId,
                null,
                $uniqueKey
            );

            return $this->kuber->getUrlFromFile( $file );
        }

        // File exists
        return $this->kuber->getUrl(
            Kuber::KUBER_ENTITY_SPOTLIGHT,
            Kuber::KUBER_TYPE_SPOTLIGHT_IMAGE,
            $spotlightId
        );
    }

    public function getInterviewImage($imageUrl, $interviewId)
    {
        $uniqueKey = 'interview_'. basename( $imageUrl );

        // Check if the file exists or has changed.
        if( $this->kuber->hasFileChanged( Kuber::KUBER_ENTITY_INTERVIEW,Kuber::KUBER_TYPE_COURSE_INTERVIEW_IMAGE, $interviewId ,$uniqueKey ) )
        {
            // Upload the hew file
            $croppedImageUrl = $this->cropImage( $imageUrl, 400, 400 );

            // Upload the file
            $filePath = '/tmp/modified_'.$uniqueKey;
            file_put_contents($filePath,file_get_contents($croppedImageUrl));

            $file = $this->kuber->upload(
                $filePath,
                Kuber::KUBER_ENTITY_INTERVIEW,
                Kuber::KUBER_TYPE_COURSE_INTERVIEW_IMAGE,
                $interviewId,
                null,
                $uniqueKey
            );

            return $this->kuber->getUrlFromFile( $file );
        }

        // File exists
        return $this->kuber->getUrl(
            Kuber::KUBER_ENTITY_INTERVIEW,
            Kuber::KUBER_TYPE_COURSE_INTERVIEW_IMAGE,
            $interviewId
        );
    }

} 