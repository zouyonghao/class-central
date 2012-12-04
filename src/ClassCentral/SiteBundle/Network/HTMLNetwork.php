<?php

namespace ClassCentral\SiteBundle\Network;

use ClassCentral\SiteBundle\Network\NetworkAbstractInterface;
use ClassCentral\SiteBundle\Entity\Offering;

class HTMLNetwork extends NetworkAbstractInterface
{
    public function outInitiative( $initiative , $offeringCount)
    {        
        $name = $initiative->getName();
        $url = $initiative->getUrl();
        $this->output->writeln("<h1><a href='$url'>$name ($offeringCount)</a></h1> ");
    }

    public function beforeOffering()
    {
        return;
    }

    public function outOffering(Offering $offering)
    {
        // Print the title line
        $titleLine = $offering->getName();
        $url = $offering->getUrl();
        $this->output->writeln("<a href='$url'>$titleLine</a>");

        $secondLine = array();
        if ($offering->getStatus() == Offering::START_DATES_KNOWN)
        {
            $secondLine[] = $offering->getStartDate()->format('M jS');
        }

        // Print out the course length. Exclude Udacity because course length is same
        if ($offering->getInitiative()->getCode() != 'UDACITY' && $offering->getLength() != 0)
        {
            $secondLine[] = $offering->getLength() . " weeks long";
        }

        if (!empty($secondLine))
        {
            $this->output->writeln("<i>" . implode(' | ', $secondLine) . "</i>");
        }
    }
}