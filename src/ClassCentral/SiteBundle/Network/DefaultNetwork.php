<?php

namespace ClassCentral\SiteBundle\Network;

use ClassCentral\SiteBundle\Network\NetworkAbstractInterface;
use ClassCentral\SiteBundle\Entity\Offering;

class DefaultNetwork extends NetworkAbstractInterface
{
    public function outInitiative( $initiative , $offeringCount)
    {
       $this->output->writeln(strtoupper($initiative) . "({$offeringCount})"); 
    }

    public function beforeOffering()
    {
        return;
    }

    public function outOffering(Offering $offering)
    {
        // Print the title line
        $titleLine =  $offering->getName();
        if($offering->getStatus() == Offering::START_DATES_KNOWN)
        {
            $titleLine .= ' - ' . $offering->getStartDate()->format('M jS');

        }
        $this->output->writeln( $titleLine);
        $this->output->writeln($offering->getUrl());

        $this->output->writeln(' ');

    }
}

