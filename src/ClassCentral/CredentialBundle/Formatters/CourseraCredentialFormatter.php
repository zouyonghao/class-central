<?php

namespace ClassCentral\CredentialBundle\Formatters;
use ClassCentral\CredentialBundle\Formatters\CredentialFormatterAbstract;

/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 9/12/15
 * Time: 11:37 PM
 */
class CourseraCredentialFormatter extends CredentialFormatterAbstract {

    public function getCertificateName()
    {
        return 'Specialization';
    }

    public function getCertificateSlug()
    {
        return 'specialization';
    }
}