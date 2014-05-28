<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/27/14
 * Time: 8:45 PM
 */

namespace ClassCentral\SiteBundle\Tests\Utility;


use ClassCentral\SiteBundle\Utility\CryptUtility;

class CryptUtilityTest extends \PHPUnit_Framework_TestCase {

    public function testEncryptDecrypt()
    {
        $str = 'randomstring#12312321';
        $key = 'RandomlyGeneratedKey';

        $encrypted = CryptUtility::encrypt($str, $key);
        $decrypted = CryptUtility::decrypt($encrypted, $key);

        $this->assertEquals($str,$decrypted);
    }
} 