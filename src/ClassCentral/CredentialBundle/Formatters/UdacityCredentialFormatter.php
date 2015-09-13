<?php

namespace ClassCentral\CredentialBundle\Formatters;
use ClassCentral\CredentialBundle\Formatters\CredentialFormatterAbstract;

/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 9/12/15
 * Time: 11:38 PM
 */
class UdacityCredentialFormatter extends CredentialFormatterAbstract {

    protected function getCertificateName()
    {
        return 'Nanodegree';
    }

    protected function getCertificateSlug()
    {
        return 'nanodegree';
    }
}