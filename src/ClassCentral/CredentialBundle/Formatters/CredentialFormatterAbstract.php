<?php

namespace ClassCentral\CredentialBundle\Formatters;

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

    abstract protected function getCertificateName();
    abstract protected function getCertificateSlug();

    public function getName()
    {
        return $this->credential->getName();
    }

    public function getDisplayPrice()
    {
        switch( $this->credential->getPricePeriod() ){
            case self::CREDENTIAL_PRICE_PERIOD_MONTHLY:
                return '$' . $this->credential->getPrice(). '/month';
                break;
            case self::CREDENTIAL_PRICE_PERIOD_TOTAL:
                return '$' . $this->credential->getPrice();
        }
    }

    public function getDisplayDuration()
    {
        if( $this->credential->getDurationMin() && $this->credential->getDurationMax() )
        {
            if ($this->credential->getDurationMin() == $this->credential->getDurationMax() )
            {
                return "{$this->credential->getDurationMin()} months";
            }
            else
            {
                return "{$this->credential->getDurationMin()}-{$this->getDurationMax()} months";
            }

        }
        return '';
    }

    public function getDisplayWorkload()
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
                case self::CREDENTIAL_WORKLOAD_TYPE_HOURS_PER_WEEK:
                    $effort .= ' hours a week';
                    break;
                case self::CREDENTIAL_WORKLOAD_TYPE_TOTAL_HOURS:
                    $effort .= ' total hours';
                    break;
            }

            return $effort;

        }

        return '';
    }


}