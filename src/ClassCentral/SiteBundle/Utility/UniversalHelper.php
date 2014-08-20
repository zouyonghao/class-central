<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 8/15/14
 * Time: 5:02 PM
 */

namespace ClassCentral\SiteBundle\Utility;


use Symfony\Component\HttpFoundation\Response;

class UniversalHelper {

    /**
     * Standard format for ajax api call response
     * @param bool $success
     * @param string $message
     */
    public  static function getAjaxResponse($success = false, $message = '')
    {
        $response = array(
            'success' => $success,
            'message' => $message
        );

        return new Response(json_encode($response));
    }
} 