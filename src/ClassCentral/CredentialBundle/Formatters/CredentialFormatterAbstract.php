<?php

namespace ClassCentral\CredentialBundle\Formatters;
use ClassCentral\CredentialBundle\Entity\Credential;

/**
 * Base class for formatting text based on Credential Provider
 * i.e Coursera, Udacity
 * User: dhawal
 * Date: 9/12/15
 * Time: 11:28 PM
 */
abstract class CredentialFormatterAbstract
{
    private $credential = null;

    public function __construct(\ClassCentral\CredentialBundle\Entity\Credential $credential)
    {
        $this->credential = $credential;
    }

    abstract public function getCertificateName();
    abstract public function getCertificateSlug();

    public function getName()
    {
        return $this->credential->getName();
    }

    public function getPrice()
    {
        switch( $this->credential->getPricePeriod() ){
            case Credential::CREDENTIAL_PRICE_PERIOD_MONTHLY:
                return '$' . $this->credential->getPrice(). '/month';
                break;
            case Credential::CREDENTIAL_PRICE_PERIOD_TOTAL:
                return '$' . $this->credential->getPrice();
        }
    }

    public function getDuration()
    {
        if( $this->credential->getDurationMin() && $this->credential->getDurationMax() )
        {
            if ($this->credential->getDurationMin() == $this->credential->getDurationMax() )
            {
                return "{$this->credential->getDurationMin()} months";
            }
            else
            {
                return "{$this->credential->getDurationMin()}-{$this->credential->getDurationMax()} months";
            }

        }
        return '';
    }

    public function getWorkload()
    {

        if( $this->credential->getWorkloadMin() && $this->credential->getWorkloadMax() )
        {
            $effort = '';
            if( $this->credential->getWorkloadMin() == $this->credential->getWorkloadMax() )
            {
                $effort = $this->credential->getWorkloadMin();
            }
            else
            {
                $effort = "{$this->credential->getWorkloadMin()}-{$this->credential->getWorkloadMax()}";
            }

            switch($this->credential->getWorkloadType())
            {
                case Credential::CREDENTIAL_WORKLOAD_TYPE_HOURS_PER_WEEK:
                    $effort .= ' hours a week';
                    break;
                case Credential::CREDENTIAL_WORKLOAD_TYPE_TOTAL_HOURS:
                    $effort .= ' total hours';
                    break;
            }

            return $effort;

        }

        return '';
    }


}