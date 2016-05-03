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
        return 'Flatiron School Certified';
    }

    public function getCertificateSlug()
    {
        // TODO: Implement getCertificateSlug() method.
    }
    
    public function getWorkload()
    {
        return 'Part time, or full time';
    }

    public function getButtonCTA()
    {
        return 'Get started for Free';
    }
}