<?php

namespace ClassCentral\SiteBundle\Network;
 
use Symfony\Component\Console\Output\OutputInterface;

class NetworkFactory
{

    public static function get($network,OutputInterface $output)
    {
        if(empty($network))
        {
            $network = 'Default';
        }
        $network = ucwords( $network );
        $class = "ClassCentral\\SiteBundle\\Network\\" . $network . "Network";
        $obj = new $class($output);
        return $obj;
    }
}
