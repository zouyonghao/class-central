<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 10/11/15
 * Time: 11:05 AM
 */

namespace ClassCentral\CredentialBundle\Formatters;


class HBXCredentialFormatter extends CredentialFormatterAbstract
{

    public function getCertificateName()
    {
        return 'HBX CORe';
    }

    public function getCertificateSlug()
    {
        return 'hbxcore';
    }

    public function getWorkload()
    {
        return "Estimated 150 hours of learning on HBX platform";
    }


}