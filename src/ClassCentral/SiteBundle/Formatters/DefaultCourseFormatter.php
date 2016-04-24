<?php

namespace ClassCentral\SiteBundle\Formatters;

use ClassCentral\SiteBundle\Entity\Course;

class DefaultCourseFormatter extends CourseFormatterAbstract
{

    public function getPrice()
    {
        if($this->course->getPrice() > 0)
        {
            switch($this->course->getPricePeriod())
            {
                case Course::PRICE_PERIOD_MONTHLY:
                    return '$' . $this->course->getPrice(). '/month';
                case Course::PRICE_PERIOD_TOTAL:
                    return'$'. $this->course->getPrice();
            }
        }

        return '0';
    }

    public function getDuration()
    {

    }

    public function getWorkload()
    {
        $effort = '';
        if( $this->course->getWorkloadMin() && $this->course->getWorkloadMax() )
        {
            if( $this->course->getWorkloadMin() == $this->course->getWorkloadMax() )
            {
                $effort = $this->course->getWorkloadMin();
            }
            else
            {
                $effort = "{$this->course->getWorkloadMin()}-{$this->course->getWorkloadMax()}";
            }

            switch($this->course->getWorkloadType())
            {
                case Course::WORKLOAD_TYPE_HOURS_PER_WEEK:
                    $effort .= ' hours a week';
                    break;
                case Course::WORKLOAD_TYPE_TOTAL_HOURS:
                    $effort .= ' hours worth of material';
                    break;
            }
        }

        return $effort;
    }

    public function getCertificate()
    {
        $str = '';

        if($this->course->getCertificate())
        {
            if($this->course->getCertificatePrice() == Course::PAID_CERTIFICATE)
            {
                $str = 'Paid Certificate Available';
            }
            elseif ($this->course->getCertificatePrice() > 0)
            {
                $str = '$' . $this->course->getCertificatePrice() . ' Certificate Available';
            }
            else
            {
                $str = 'Certificate Available';
            }
        }

        return $str;
    }
}