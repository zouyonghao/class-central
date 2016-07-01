<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 6/8/16
 * Time: 11:28 AM
 */

namespace ClassCentral\CredentialBundle\Formatters;


class FutureLearnCredentialFormatter extends CredentialFormatterAbstract
{
    public function getCertificateName()
    {
        return 'FutureLearn Programs';
    }

    public function getCertificateSlug()
    {
        return 'programs';
    }

    public function getPrice()
    {
        return '£' . $this->credential->getPrice();
    }

    public function getDuration()
    {
        if( $this->credential->getDurationMin() && $this->credential->getDurationMax() )
        {
            if ($this->credential->getDurationMin() == $this->credential->getDurationMax() )
            {
                return "{$this->credential->getDurationMin()} weeks";
            }
            else
            {
                return "{$this->credential->getDurationMin()}-{$this->credential->getDurationMax()} weeks";
            }

        }
        return '';
    }
}