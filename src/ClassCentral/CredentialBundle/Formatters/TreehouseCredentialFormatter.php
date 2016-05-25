<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/24/16
 * Time: 8:26 PM
 */

namespace ClassCentral\CredentialBundle\Formatters;


class TreehouseCredentialFormatter extends CredentialFormatterAbstract
{

    public function getCertificateName()
    {
        return 'Techdegree';
    }

    public function getCertificateSlug()
    {
        return 'techdegree';
    }

    public function getButtonCTA()
    {
        return 'Go To Techdegree';
    }
}