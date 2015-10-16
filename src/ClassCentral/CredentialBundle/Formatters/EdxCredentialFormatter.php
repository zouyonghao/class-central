<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 10/14/15
 * Time: 10:41 PM
 */

namespace ClassCentral\CredentialBundle\Formatters;


class EdxCredentialFormatter extends CredentialFormatterAbstract
{

    public function getCertificateName()
    {
        return 'XSeries';
    }

    public function getCertificateSlug()
    {
        return 'xseries';
    }
}