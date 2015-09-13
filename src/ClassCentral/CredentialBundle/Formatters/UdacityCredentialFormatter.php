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

    public function getCertificateName()
    {
        return 'Nanodegree';
    }

    public function getCertificateSlug()
    {
        return 'nanodegree';
    }

    public function getWorkload()
    {
        return 'Minimum ' . $this->credential->getWorkloadMin() . 'hrs/week';
    }

}