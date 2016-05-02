<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 5/2/16
 * Time: 3:30 PM
 */

namespace ClassCentral\CredentialBundle\Formatters;


class FlatironCredentialFormatter extends CredentialFormatterAbstract
{

    public function getCertificateName()
    {
        return 'Learn Verified';
    }

    public function getCertificateSlug()
    {
        // TODO: Implement getCertificateSlug() method.
    }
}