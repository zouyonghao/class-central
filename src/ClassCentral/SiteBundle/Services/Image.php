<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 4/4/15
 * Time: 6:56 PM
 */

namespace ClassCentral\SiteBundle\Services;


/**
 * Image Manipulation library
 * @package ClassCentral\SiteBundle\Services
 */
class Image {

    private  $apiKey;
    private  $embedlyDisplayBaseUrl = 'https://i.embed.ly/1/display';

    public function __construct( $apiKey )
    {
        $this->apiKey = $apiKey;
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
        sprintf('/crop?url=%s&key=%s&height=%d&width=%d&grow=true',$imageUrl,$this->apiKey,$height,$width);
    }
} 