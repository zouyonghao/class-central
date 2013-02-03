<?php

namespace ClassCentral\SiteBundle\Command\Network;

use Symfony\Component\Console\Output\OutputInterface;
use ClassCentral\SiteBundle\Entity\Offering;

abstract class NetworkAbstractInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    abstract public function outInitiative($initiative, $offeringCount);

    abstract public function beforeOffering();
    abstract public function outOffering(Offering $offering);

}
