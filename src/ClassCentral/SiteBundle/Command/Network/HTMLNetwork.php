<?php

namespace ClassCentral\SiteBundle\Command\Network;

use ClassCentral\SiteBundle\Command\Network\NetworkAbstractInterface;
use ClassCentral\SiteBundle\Entity\Offering;

class HTMLNetwork extends NetworkAbstractInterface
{
    public function outInitiative( $stream , $offeringCount)
    {
//        if($initiative == null)
//        {
//            $name = 'Others';
//            $url = '';
//        }
//        else
//        {
//            $name = $initiative->getName();
//            $url = $initiative->getUrl();
//        }
          $name   = $stream->getName();
          $url = "http://www.class-central.com/stream/". $stream->getSlug();


        $this->output->writeln("<h1><a href='$url'>$name ($offeringCount)</a></h1>");
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
        $url = 'http://www.class-central.com'. $this->router->generate('ClassCentralSiteBundle_mooc', array('id' => $offering->getCourse()->getId(), 'slug' => $offering->getCourse()->getSlug()));
        $this->output->writeln("<a href='$url'>$titleLine</a>");

        $secondLine = array();
        if ($offering->getStatus() == Offering::START_DATES_KNOWN)
        {
            $secondLine[] = $offering->getStartDate()->format('M jS');
        }

        $initiative = $offering->getInitiative();
        if($initiative == null)
        {
            $name = 'Others';
            $url = '';
        }
        else
        {
            $name = $initiative->getName();
            $url = $initiative->getUrl();
        }

        // Print out the course length. Exclude Udacity because course length is same
        if (  $initiative != 'UDACITY' && $offering->getLength() != 0)
        {
            $secondLine[] = $offering->getLength() . " weeks long";
        }

        $secondLine[] = $name;
        if (!empty($secondLine))
        {
            $this->output->writeln("<i>" . implode(' | ', $secondLine) . "</i>");
        }
    }
}
