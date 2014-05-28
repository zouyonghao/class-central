<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/27/14
 * Time: 8:36 PM
 */

namespace ClassCentral\SiteBundle\Utility;


class CryptUtility {

    /**
     * Encrypts a string
     * @param $string
     * @param $key
     * @return string
     */
    public static function encrypt($string , $key)
    {
            return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
    }

    /**
     * Decrypts a string
     * @param $encrypted
     * @param $key
     * @return string
     */
    public static function decrypt($encrypted, $key)
    {
           return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($encrypted), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
    }
} 